<?php
namespace Chiara\PodioApp\Fields;
use Chiara\PodioApp\Field;
class Money extends Field
{
    function __construct(array $info = array())
    {
        parent::__construct($info);
        $this->info['config']['settings']['allowed_currencies'] = array();
    }

    function __get($var)
    {
        if ($var == 'allowed_currencies') return $this->info['config']['settings']['allowed_currencies'];
        return parent::__get($var);
    }
}