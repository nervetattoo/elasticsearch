<?php

namespace ElasticSearch\tests;

class Helper
{
    protected static function generateDocument($words, $len=4) {
        $sentence = "";
        while ($len > 0) {
            shuffle($words);
            $sentence .= $words[0] . " ";
            $len--;
        }
        return array('title' => $sentence, 'rank' => rand(1, 10));
    }

    public static function addDocuments(\ElasticSearch\Client $client, $num = 3, $tag = 'cool')
    {
        $options = array('refresh' => true);
        while ($num-- > 0) {
            $doc = array('title' => "One cool document $tag", 'rank' => rand(1,10));
            $client->index($doc, $num + 1, $options);
        }
        return $client;
    }
}
