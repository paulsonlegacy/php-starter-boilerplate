<?php

use PHPUnit\Framework\TestCase;

class HelpersTest extends TestCase
{
    public function test_e_escapes_html(): void
    {
        $this->assertSame(
            '&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;',
            e('<script>alert("xss")</script>')
        );
    }

    public function test_e_handles_apostrophe(): void
    {
        $this->assertSame("O&#039;Brien", e("O'Brien"));
    }

    public function test_e_handles_empty_string(): void
    {
        $this->assertSame('', e(''));
    }

    public function test_generate_unique_id_length(): void
    {
        $id = generate_unique_id(12);
        $this->assertSame(12, strlen($id));
    }

    public function test_generate_unique_id_default_length(): void
    {
        $id = generate_unique_id();
        $this->assertSame(10, strlen($id));
    }

    public function test_generate_unique_id_alphanumeric(): void
    {
        $id = generate_unique_id(100);
        $this->assertMatchesRegularExpression('/^[0-9A-Z]+$/', $id);
    }

    public function test_format_date(): void
    {
        $this->assertSame('15 Jan, 2025', format_date('2025-01-15'));
    }

    public function test_format_datetime(): void
    {
        $result = format_datetime('2025-01-15 14:30:00');
        $this->assertSame('15 Jan, 2025  02:30 PM', $result);
    }

    public function test_format_datetime_timezone(): void
    {
        $result = format_datetime_timezone('2025-01-15 14:30:00', 'Africa/Lagos');
        $this->assertStringContainsString('15 Jan, 2025', $result);
    }
}
