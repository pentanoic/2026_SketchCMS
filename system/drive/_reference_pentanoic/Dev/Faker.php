<?php
/**
 * Author: valedrat
 * Product: phpSketch / phpDQuery
 *
 * Faker — zero-dependency fake data generator.
 * No external library needed. Covers the most common data types.
 *
 * Available methods:
 *   $f->name()              → 'Nguyen Van A'
 *   $f->firstName()         → 'Minh'
 *   $f->lastName()          → 'Tran'
 *   $f->email()             → 'minh.tran@example.com'
 *   $f->username()          → 'minh_tran_42'
 *   $f->password(len)       → random hex string
 *   $f->phone()             → '090xxxxxxx'
 *   $f->word()              → single random word
 *   $f->words(n)            → 'foo bar baz'
 *   $f->sentence()          → 'Lorem ipsum ...'
 *   $f->paragraph()         → multiple sentences
 *   $f->slug()              → 'some-random-slug'
 *   $f->url()               → 'https://example.com/path'
 *   $f->ipv4()              → '192.168.x.x'
 *   $f->int(min, max)       → random int
 *   $f->float(min, max, dp) → random float
 *   $f->bool(chance)        → true/false (chance = 0.5 default)
 *   $f->pick(array)         → random element from array
 *   $f->picks(array, n)     → n random elements
 *   $f->uuid()              → v4 UUID string
 *   $f->date(from, to)      → 'YYYY-MM-DD'
 *   $f->datetime(from, to)  → 'YYYY-MM-DD HH:MM:SS'
 *   $f->timestamp(from, to) → Unix timestamp int
 *   $f->json(array)         → JSON string of provided array (with fake values)
 *   $f->sequence(n)         → sequential int (increments per call)
 */

namespace PhpDQuery\Dev;

class Faker
{
    private static array $firstNames = [
        'An','Binh','Chi','Dung','Em','Giang','Hai','Hung','Lan','Mai',
        'Nam','Oanh','Phuong','Quang','Thu','Thuy','Tuan','Van','Yen','Minh',
        'Alice','Bob','Carol','Dave','Eve','Frank','Grace','Heidi','Ivan','Judy',
    ];

    private static array $lastNames = [
        'Nguyen','Tran','Le','Pham','Hoang','Phan','Vu','Dang','Bui','Do',
        'Ho','Ngo','Duong','Ly','Smith','Johnson','Williams','Brown','Jones','Garcia',
    ];

    private static array $words = [
        'apple','brave','cloud','dream','edge','flame','grove','hope','iron','jade',
        'keen','light','mist','noble','orbit','prime','quest','river','stone','tide',
        'ultra','vivid','wave','xenon','yield','zenith','alpha','beta','gamma','delta',
        'echo','folio','glyph','helix','image','jumbo','kappa','lunar','matrix','nexus',
    ];

    private static array $domains = [
        'example.com','mail.com','inbox.net','webmail.org','fastmail.io',
        'testmail.dev','demo.app','sample.vn','phpsketch.dev',
    ];

    private static array $tlds = ['com','net','org','io','dev','app','vn'];

    private int $sequenceCounter = 0;

    // ─── Personal ─────────────────────────────────────────────────────────────

    public function firstName(): string
    {
        return self::$firstNames[array_rand(self::$firstNames)];
    }

    public function lastName(): string
    {
        return self::$lastNames[array_rand(self::$lastNames)];
    }

    public function name(): string
    {
        return $this->firstName() . ' ' . $this->lastName();
    }

    public function email(): string
    {
        $fn     = strtolower($this->firstName());
        $ln     = strtolower($this->lastName());
        $domain = self::$domains[array_rand(self::$domains)];
        $num    = $this->int(1, 999);
        return "{$fn}.{$ln}{$num}@{$domain}";
    }

    public function username(): string
    {
        $fn  = strtolower($this->firstName());
        $ln  = strtolower($this->lastName());
        $num = $this->int(1, 999);
        return "{$fn}_{$ln}_{$num}";
    }

    public function password(int $length = 32): string
    {
        return substr(bin2hex(random_bytes($length)), 0, $length);
    }

    public function phone(): string
    {
        $prefix = $this->pick(['090','091','093','094','096','097','098','070','079','077','076','078','032','033','034','035','036','037','038','039']);
        return $prefix . sprintf('%07d', $this->int(0, 9999999));
    }

    // ─── Text ─────────────────────────────────────────────────────────────────

    public function word(): string
    {
        return self::$words[array_rand(self::$words)];
    }

    public function words(int $n = 3): string
    {
        $out = [];
        for ($i = 0; $i < $n; $i++) {
            $out[] = $this->word();
        }
        return implode(' ', $out);
    }

    public function sentence(int $words = 8): string
    {
        return ucfirst($this->words($words)) . '.';
    }

    public function paragraph(int $sentences = 4): string
    {
        $out = [];
        for ($i = 0; $i < $sentences; $i++) {
            $out[] = $this->sentence($this->int(6, 12));
        }
        return implode(' ', $out);
    }

    public function slug(int $words = 3): string
    {
        $w = [];
        for ($i = 0; $i < $words; $i++) {
            $w[] = $this->word();
        }
        return implode('-', $w);
    }

    public function title(int $words = 5): string
    {
        return ucwords($this->words($words));
    }

    // ─── Internet ─────────────────────────────────────────────────────────────

    public function url(): string
    {
        $domain = self::$domains[array_rand(self::$domains)];
        return 'https://' . $domain . '/' . $this->slug();
    }

    public function ipv4(): string
    {
        return implode('.', [$this->int(1,254), $this->int(0,255), $this->int(0,255), $this->int(1,254)]);
    }

    public function domain(): string
    {
        return $this->slug(1) . '.' . self::$tlds[array_rand(self::$tlds)];
    }

    // ─── Numeric ──────────────────────────────────────────────────────────────

    public function int(int $min = 0, int $max = PHP_INT_MAX): int
    {
        return random_int($min, $max);
    }

    public function float(float $min = 0.0, float $max = 1.0, int $decimals = 2): float
    {
        $val = $min + lcg_value() * ($max - $min);
        return round($val, $decimals);
    }

    public function bool(float $chance = 0.5): bool
    {
        return lcg_value() < $chance;
    }

    public function sequence(int $start = 1): int
    {
        return $start + ($this->sequenceCounter++);
    }

    // ─── Array / Pick ─────────────────────────────────────────────────────────

    public function pick(array $items): mixed
    {
        return $items[array_rand($items)];
    }

    public function picks(array $items, int $n = 2): array
    {
        $keys = array_rand($items, min($n, count($items)));
        if (!is_array($keys)) $keys = [$keys];
        return array_map(fn($k) => $items[$k], $keys);
    }

    // ─── UUID ─────────────────────────────────────────────────────────────────

    public function uuid(): string
    {
        $data    = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // version 4
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // variant RFC 4122
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    // ─── Date / Time ──────────────────────────────────────────────────────────

    public function timestamp(string $from = '-2 years', string $to = 'now'): int
    {
        $start = strtotime($from);
        $end   = strtotime($to);
        return random_int(min($start, $end), max($start, $end));
    }

    public function date(string $from = '-2 years', string $to = 'now'): string
    {
        return date('Y-m-d', $this->timestamp($from, $to));
    }

    public function datetime(string $from = '-2 years', string $to = 'now'): string
    {
        return date('Y-m-d H:i:s', $this->timestamp($from, $to));
    }

    // ─── Misc ─────────────────────────────────────────────────────────────────

    public function json(array $data): string
    {
        return json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    public function hex(int $length = 16): string
    {
        return bin2hex(random_bytes((int)ceil($length / 2)));
    }

    public function color(): string
    {
        return '#' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
    }
}
