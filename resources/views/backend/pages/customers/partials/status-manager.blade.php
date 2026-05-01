<div class="mt-4">
	<h3 class="text-lg font-semibold mb-4">{{ __('Status Manager') }}</h3>
	<div class="overflow-x-auto rounded border border-gray-200 dark:border-gray-700">
		<div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded p-6 space-y-5">
			@if (session()->has('success'))
				<div class="rounded-lg border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-800 dark:border-green-800 dark:bg-green-950/30 dark:text-green-200">
					{{ session('success') }}
				</div>
			@endif

			@if ($errors->any())
				<div class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800 dark:border-red-800 dark:bg-red-950/30 dark:text-red-200">
					{{ $errors->first() }}
				</div>
			@endif

					{{-- Company Name --}}
					<div>
						<label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 tracking-wide uppercase">
							{{ __('Company Name') }}
						</label>
						<div class="relative">
							<select
								wire:model="selectedCompany"
								class="w-full appearance-none border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 pr-8 text-sm bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500 cursor-pointer">
								<option value="">{{ __('Select company…') }}</option>
								@foreach ($managerCompanies as $company)
								<option value="{{ $company }}" {{ $selectedCompany === $company ? 'selected' : '' }}>
									{{ $company }}
								</option>
								@endforeach
							</select>
							<svg class="absolute right-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400 pointer-events-none" fill="none" viewBox="0 0 12 12">
								<path d="M3 4.5l3 3 3-3" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" />
							</svg>
						</div>
					</div>

					{{-- Can See Interview Reports --}}
					<div
						class="flex items-center gap-3 px-3.5 py-3 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg cursor-pointer"
						wire:click="$toggle('canViewReport')">
						<div class="w-4 h-4 rounded border-2 flex items-center justify-center flex-shrink-0
                    {{ $canViewReport ? 'bg-red-600 border-red-600' : 'bg-white dark:bg-gray-900 border-gray-400' }}">
							@if($canViewReport)
							<svg class="w-2.5 h-2.5 text-white" fill="none" viewBox="0 0 10 10">
								<path d="M2 5l2.5 2.5L8 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
							</svg>
							@endif
						</div>
						<span class="text-sm font-medium text-gray-800 dark:text-gray-200">
							{{ __('Can see Interview Reports') }}
						</span>
					</div>

					<hr class="border-gray-100 dark:border-gray-700">

					{{-- Interview Report Upload Email Template --}}
					<div>
						<label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1 tracking-wide uppercase">
							<span class="text-red-600">Note:</span>
							{{ __('Interview Report Upload Email Template') }}
							<span class="normal-case font-normal text-gray-400 ml-1">
								— {{ __('leave empty to skip sending email on upload') }}
							</span>
						</label>
						<!-- <div class="relative">
							<textarea
								wire:model="interviewReportTemplate"
								rows="4"
								placeholder="{{ __('Enter HTML email template…') }}"
								class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2.5 text-xs font-mono bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-gray-100 resize-y focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500"></textarea>
							<span class="absolute top-2 right-2 text-[10px] text-gray-400 bg-gray-100 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded px-1.5 py-0.5">HTML</span>
						</div> -->
					</div>

					{{-- Two-column Email Templates --}}
					<div class="grid grid-cols-1 md:grid-cols-2 gap-4">

						{{-- Under Investigation --}}
						<div>
							<label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1 tracking-wide uppercase">
								{{ __('Under Investigation Email Template') }}
							</label>
							<div class="relative">
								<textarea
									wire:model="underInvestigationTemplate"
									rows="5"
									class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2.5 text-xs font-mono bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-gray-100 resize-y focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500"></textarea>
								<span class="absolute top-2 right-2 text-[10px] text-gray-400 bg-gray-100 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded px-1.5 py-0.5">HTML</span>
							</div>
						</div>

						{{-- Approved --}}
						<div>
							<label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1 tracking-wide uppercase">
								{{ __('Approved Email Template') }}
							</label>
							<div class="relative">
								<textarea
									wire:model="approvedTemplate"
									rows="5"
									class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2.5 text-xs font-mono bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-gray-100 resize-y focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500"></textarea>
								<span class="absolute top-2 right-2 text-[10px] text-gray-400 bg-gray-100 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded px-1.5 py-0.5">HTML</span>
							</div>
						</div>

					</div>

					{{-- Footer --}}
					<div class="flex items-center justify-between pt-1">
						<p class="text-xs text-gray-400 flex items-center gap-1.5">
							<svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" viewBox="0 0 12 12">
								<circle cx="6" cy="6" r="5" stroke="currentColor" stroke-width="1.2" />
								<path d="M6 5v4M6 3.5v.5" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" />
							</svg>
							{{ __('Templates support HTML. Changes apply immediately after update.') }}
						</p>
						<button
							wire:click="update"
							wire:loading.attr="disabled"
							class="flex items-center gap-1.5 bg-red-600 hover:bg-red-700 disabled:opacity-60 text-white text-sm font-medium px-5 py-2 rounded-lg transition-colors">
							<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 13 13">
								<path d="M2 6.5A4.5 4.5 0 1 0 6.5 2" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" />
								<path d="M2 3.5V6.5H5" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round" />
							</svg>
							<span wire:loading.remove wire:target="update">{{ __('Update') }}</span>
							<span wire:loading wire:target="update">{{ __('Saving…') }}</span>
						</button>
					</div>

		</div>
		</div>
	</div>
</div>