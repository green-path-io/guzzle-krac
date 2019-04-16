<?php


namespace Greenpath\GuzzleKrac;


interface Request
{
    function credentials(string $key, string $secret);
    function data(array $data);
    function query(array $query);
    function headers(array $headers);
    function filters(array $filters);
    function form_params(array $formparams);
}