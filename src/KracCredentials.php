<?php
namespace Greenpath\GuzzleKrac;

interface KracCredentials
{
    public function credentials(string $key, string $secret);
}