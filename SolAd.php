<?php

 // A single ad object

class SolAd {

    // uuid (string)
    public $UUID     

    // string 
    // should be one sentence
    public $title     

    // string
    // boy of ad. should be a couple of paragraphs
    // may contain HTML
    public $body

    // string
    // comma delimited keywords
    public $keywords

    // string?
    public $language

    // string?
    public $location

    // integer
    // must be 0-3, values:
    // 0 - ?
    // 1 - ? ... ?
    public $scope, integer from 0-3

    // ?????
    // when the scope goes to zero
    public $expiry

    // string
    // URL of ad
    public $url

}
