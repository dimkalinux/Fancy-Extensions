<?php

class FancyMedia
{
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
        return isset($this->service[$serviceId]) ? $this->service[$serviceId] : $this->service['unknown'];
    }

    public function parseUrl($url)
    {
        $videoUri = NULL;
        $match = array();

        // Dirty trick to play arround do_clickable.
        preg_match('`href="([^"]+)"`', stripslashes($videoUri), $match);
        if (!empty($match[1])) {
            $videoUri = $match[1];
        }

        // Extract service's name and check for support.
        $match = array();
        preg_match('`^(?:http|https)://(?:[^\.]*\.)?([^\.]*)\.[^/]*/`i', $videoUri, $match);
        if (empty($match[1]) || !array_key_exists($match[1], $service)) {
            return '<a href="'.$videoUri.'">['.$fancy_video_tag["unknown_source"].']</a>';
        }

        $service = $this->getService($match[1]);
        return $service->getWidget($videoUri);

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