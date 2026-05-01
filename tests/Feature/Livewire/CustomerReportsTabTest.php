<?php

declare(strict_types=1);

use App\Livewire\Backend\Reports\Templates as ReportTemplates;
use App\Livewire\Customer\Tabs\Reports as CustomerReports;
use App\Models\Customer;
use App\Models\ServiceCategory;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

pest()->use(RefreshDatabase::class);

it('saves global swedish and english templates from reports page', function () {
    $backgroundCategory = ServiceCategory::query()->create([
        'id' => 2,
        'name' => 'Background Check',
        'name_sv' => 'Bakgrundskontroll',
    ]);

    $service = ServiceType::query()->create([
        'service_category_id' => $backgroundCategory->id,
        'name' => 'Background Service A',
        'price' => 0,
    ]);

    $component = Livewire::test(ReportTemplates::class)
        ->set('selectedService', $service->id);

    $svNewSectionIndex = count($component->get('templates.sv.sections'));
    $component
        ->call('addTextSection', 'sv')
        ->set("templates.sv.sections.{$svNewSectionIndex}.heading", 'Information & Facts')
        ->set("templates.sv.sections.{$svNewSectionIndex}.content", 'Svensk global mall');

    $enNewSectionIndex = count($component->get('templates.en.sections'));
    $component
        ->call('addTableSection', 'en')
        ->set("templates.en.sections.{$enNewSectionIndex}.caption", 'Profile')
        ->call('addTableRow', 'en', $enNewSectionIndex)
        ->set("templates.en.sections.{$enNewSectionIndex}.rows.1.c1", 'Status')
        ->set("templates.en.sections.{$enNewSectionIndex}.rows.1.c2", 'Approved')
        ->call('saveTemplates');

    $svPayload = json_decode((string) DB::table('customer_reports_html')
        ->where('cus_id', 0)
        ->where('interview_id', $service->id)
        ->where('lang', 'sv')
        ->value('report_data'), true);

    $enPayload = json_decode((string) DB::table('customer_reports_html')
        ->where('cus_id', 0)
        ->where('interview_id', $service->id)
        ->where('lang', 'en')
        ->value('report_data'), true);

    expect($svPayload['sections'][$svNewSectionIndex]['heading'] ?? null)->toBe('Information & Facts');
    expect($svPayload['sections'][$svNewSectionIndex]['content'] ?? null)->toBe('Svensk global mall');
    expect($enPayload['sections'][$enNewSectionIndex]['type'] ?? null)->toBe('table');
    expect($enPayload['sections'][$enNewSectionIndex]['rows'][1]['c2'] ?? null)->toBe('Approved');
});

it('loads global templates and saves customer overrides per service', function () {
    $customerUser = User::factory()->create();
    $customer = Customer::query()->create([
        'user_id' => $customerUser->id,
        'company' => 'Acme AB',
    ]);

    $backgroundCategory = ServiceCategory::query()->create([
        'id' => 2,
        'name' => 'Background Check',
        'name_sv' => 'Bakgrundskontroll',
    ]);

    $service = ServiceType::query()->create([
        'service_category_id' => $backgroundCategory->id,
        'name' => 'Background Service B',
        'price' => 0,
    ]);

    DB::table('service_type_user')->insert([
        'service_type_id' => $service->id,
        'cus_id' => $customer->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('customer_reports_html')->insert([
        [
            'cus_id' => 0,
            'interview_id' => $service->id,
            'lang' => 'sv',
            'report_data' => json_encode([
                'version' => 1,
                'sections' => [
                    ['id' => 'sv-1', 'type' => 'text', 'heading' => 'SV Heading', 'content' => 'Global Swedish', 'align' => 'left', 'status_id' => null],
                ],
            ], JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'cus_id' => 0,
            'interview_id' => $service->id,
            'lang' => 'en',
            'report_data' => json_encode([
                'version' => 1,
                'sections' => [
                    ['id' => 'en-1', 'type' => 'text', 'heading' => 'EN Heading', 'content' => 'Global English', 'align' => 'left', 'status_id' => null],
                ],
            ], JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    Livewire::test(CustomerReports::class, ['customerId' => $customer->id])
        ->set('selectedService', $service->id)
        ->assertSet('templates.sv.sections.0.content', 'Global Swedish')
        ->assertSet('templates.en.sections.0.content', 'Global English')
        ->set('templates.en.sections.0.content', 'Customer English Override')
        ->call('saveTemplates')
        ->assertSet('isOverridden.en', true)
        ->call('resetLanguageToGlobal', 'en')
        ->assertSet('templates.en.sections.0.content', 'Global English')
        ->assertSet('isOverridden.en', false);

    expect(DB::table('customer_reports_html')
        ->where('cus_id', $customer->id)
        ->where('interview_id', $service->id)
        ->where('lang', 'en')
        ->exists())->toBeFalse();
});
