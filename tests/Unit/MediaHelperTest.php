<?php

declare(strict_types=1);
use App\Support\Helper\MediaHelper;

test('can get upload limits', function () {
    $limits = MediaHelper::getUploadLimits();

    expect($limits)->toHaveKey('upload_max_filesize');
    expect($limits)->toHaveKey('post_max_size');
    expect($limits)->toHaveKey('effective_max_filesize');
    expect($limits)->toHaveKey('max_file_uploads');

    // Check that effective limit is correct
    $expected = min($limits['upload_max_filesize'], $limits['post_max_size']);
    expect($limits['effective_max_filesize'])->toEqual($expected);
});
test('can parse php size strings', function () {
    expect(MediaHelper::parseSize('1K'))->toEqual(1024);
    expect(MediaHelper::parseSize('1M'))->toEqual(1024 * 1024);
    expect(MediaHelper::parseSize('1G'))->toEqual(1024 * 1024 * 1024);
    expect(MediaHelper::parseSize('2K'))->toEqual(2048);
    expect(MediaHelper::parseSize('10M'))->toEqual(10 * 1024 * 1024);
});
test('format file size', function () {
    expect(MediaHelper::formatFileSize(1024))->toEqual('1024 B');
    expect(MediaHelper::formatFileSize(2048))->toEqual('2 KB');
    expect(MediaHelper::formatFileSize(1024 * 1024))->toEqual('1024 KB');
    expect(MediaHelper::formatFileSize(1024 * 1024 * 1024))->toEqual('1024 MB');
    expect(MediaHelper::formatFileSize(500))->toEqual('500 B');
});
