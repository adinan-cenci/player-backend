<?php
namespace AdinanCenci\Player\Source;

class Filter 
{
    protected string $title = '';

    public function __construct($parameters) 
    {
        if (!empty($parameters['title'])) {
            $this->title = $this->normalizeString($parameters['title']);
        }
    }

    public function filter(Resource $resource) : bool
    {
        $resourceTitle = $this->normalizeString($resource->title);

        if ($this->title) {
            return substr_count($resourceTitle, $this->title) > 0;
        }

        return true;
    }

    public function normalizeString(string $string) : string
    {
        $string = trim(strtolower($string));
        $string = str_replace([' '], '', $string);
        return $string;
    }
}