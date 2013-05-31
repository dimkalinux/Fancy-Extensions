<?php

class FancyMedia
{
    const UNKNOWN_SERVICE_ID = 'unknown';

    protected $language;
    protected $services;
    protected $html5Mode;

    public function __construct(Array $config, Array $language)
    {
        $this->language = $language;
        $this->services = array();

        $this->html5Mode = isset($config['o_fancy_video_tag_html5']) && $config['o_fancy_video_tag_html5'] == '1';
    }

    public function addService(FancyMediaService $service)
    {
        $service->setHtml5Mode($this->html5Mode);
        $this->services[$service->getId()] = $service;
    }

    protected function getService($serviceId)
    {
        return isset($this->service[$serviceId]) ? $this->service[$serviceId] : $this->service[self::UNKNOWN_SERVICE_ID];
    }

    public function parseUrl($url)
    {
        $videoUri = NULL;

        // Dirty trick to play arround do_clickable.
        $match = array();
        preg_match('`href="([^"]+)"`', stripslashes($videoUri), $match);
        if (!empty($match[1])) {
            $videoUri = $match[1];
        }

        $serviceId = $this->getServiceIdFromUrl($videoUri);
        $service   = $this->getService($match[1]);

        return $service->getWidget($videoUri);
    }

    protected function getServiceIdFromUrl($url)
    {
        $match = array();

        preg_match('`^(?:http|https)://(?:[^\.]*\.)?([^\.]*)\.[^/]*/`i', $url, $match);
        if (!empty($match[1])) {
            return trim($match[1]);
        }

        return self::UNKNOWN_SERVICE_ID;
    }
}

define('FANCY_MEDIA_LOADED', TRUE);
$fancyMedia = new FancyMedia($forum_config, $fancy_video_tag);
$fancyMedia->addService(new FancyMediaServiceUnknown);
$fancyMedia->addService(new FancyMediaServiceYoutube);

abstract class FancyMediaService
{
    protected $html5Mode;

    public function getId()
    {
        return self::ID;
    }

    public function setHtml5Mode($html5Mode)
    {
        $this->html5Mode = (boolean) $html5Mode;
    }

    abstract public function getWidgetCode($url);
}

// NullObject class.
class FancyMediaServiceUnknown extends FancyMediaService
{
    const ID = 'unknown';

    public function getWidgetCode($url)
    {
        // TODO: add forum_htmlencode.
        return sprintf('<a href="%s">[%s]</a>', $url, $fancy_video_tag["unknown_source"]);
    }

    protected function extractKey()
    {

    }
}

class FancyMediaServiceYoutube extends FancyMediaService
{
    const ID            = 'youtube';
    const MATCHER       = '`youtube.com.*v=([-_a-z0-9]+)`i';
    const WIDGET_WIDTH  = 640;
    const WIDGET_HEIGHT = 385;

    public function getWidgetCode($url)
    {


    }
}