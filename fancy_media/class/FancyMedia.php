<?php

class FancyMedia
{
    const UNKNOWN_SERVICE_ID = 'unknown';

    protected $services;
    protected $language;

    public function __construct(Array $language)
    {
        $this->language = $language;
    }

    public function addService(FancyMediaService $service)
    {
        $service->setLanguage($this->language);
        $this->services[$service->getId()] = $service;
    }

    protected function getService($serviceId)
    {
        return isset($this->services[$serviceId])
               ? $this->services[$serviceId]
               : $this->services[self::UNKNOWN_SERVICE_ID]
        ;
    }

    public function getWidget($url)
    {
        $mediaUrl = NULL;

        // Dirty trick to play arround do_clickable.
        $match = array();
        preg_match('`href="([^"]+)"`', stripslashes($url), $match);
        if (!empty($match[1])) {
            $mediaUrl = $match[1];
        }

        $serviceId = $this->getServiceIdFromUrl($mediaUrl);
        $service   = $this->getService($serviceId);

        return trim($service->getWidget($mediaUrl));
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
$fancyMedia = new FancyMedia($fancy_media_lang);
$fancyMedia->addService(new FancyMediaServiceUnknown);
$fancyMedia->addService(new FancyMediaServiceYoutube);
$fancyMedia->addService(new FancyMediaServiceYoutu);
$fancyMedia->addService(new FancyMediaServiceVimeo);
$fancyMedia->addService(new FancyMediaServiceSoundcloud);
$fancyMedia->addService(new FancyMediaServiceVine);
$fancyMedia->addService(new FancyMediaServiceDaylimotion);
$fancyMedia->addService(new FancyMediaServiceRutube);
$fancyMedia->addService(new FancyMediaServiceFacebook);


abstract class FancyMediaService
{
    protected $id;
    protected $matcher;
    protected $playerUrlTemplate;
    protected $html5WidgetTemplate;
    protected $widgetWidth  = 640;
    protected $widgetHeight = 385;
    protected $language;

    public function getId()
    {
        return $this->id;
    }

    public function setLanguage(Array $language)
    {
        $this->language = $language;
    }

    // Default implementation.
    public function getWidget($url)
    {
        $sourceId = $this->extractSourceId($url);

        if (is_null($sourceId)) {
            $playerCode = $this->getFailedPlayerCode($url);
        } else {
            if ($this->canPlayInHtml5Mode()) {
                $pattern         = array('%SOURCE_ID%', '%WIDTH%', '%HEIGHT%');
                $replace         = array(forum_htmlencode($sourceId), $this->widgetWidth, $this->widgetHeight);
                $frameCode  = str_replace($pattern, $replace, $this->html5WidgetTemplate);
                $playerCode = '<div class="fancy_media_player">' . $frameCode . '</div>';
            } else {
                $playerUrl  = str_replace('%SOURCE_ID%', forum_htmlencode($sourceId), $this->playerUrlTemplate);
                $playerCode = $this->getDefaultPlayerCode($playerUrl, $url);
            }
        }

        return $playerCode;
    }

    protected function canPlayInHtml5Mode()
    {
        return !empty($this->html5WidgetTemplate);
    }

    protected function extractSourceId($url)
    {
        $match = array();
        preg_match($this->matcher, $url, $match);

        return empty($match[1]) ? NULL : $match[1];
    }

    protected function getFailedPlayerCode($sourceUrl)
    {
        return '<a href="' . forum_htmlencode($sourceUrl) . '">[unknown media source]</a>';
    }

    protected function getDefaultPlayerCode($mediaUrl, $sourceUrl)
    {
        $playerTemplate = '<div class="fancy_media_player">'
                        . '<object type="application/x-shockwave-flash" data="%MEDIA_URL%" width="%WIDTH%" height="%HEIGHT%">'
                        . '<param name="movie" value="%MEDIA_URL%"/>'
                        . '<param name="wmode" value="transparent"/>'
                        . '<param name="allowfullscreen" value="true"/>'
                        . '<p><a href="%SOURCE_URL%">' . $this->language['no flash'] . '</a></p>'
                        . '</object>'
                        . '</div>';

        $a1 = array('%MEDIA_URL%', '%WIDTH%', '%HEIGHT%', '%SOURCE_URL%');
        $a2 = array(forum_htmlencode($mediaUrl), $this->widgetWidth, $this->widgetHeight, forum_htmlencode($sourceUrl));
        $renderedPlayerCode = str_replace($a1, $a2, $playerTemplate);

        return $renderedPlayerCode;
    }
}

// NullObject class.
class FancyMediaServiceUnknown extends FancyMediaService
{
    protected $id = 'unknown';

    public function getWidget($url)
    {
        return sprintf('<a href="%s">[%s]</a>', forum_htmlencode($url), $this->language['unknown_source']);
    }
}

// Youtube.
class FancyMediaServiceYoutube extends FancyMediaService
{
    protected $id                  = 'youtube';
    protected $matcher             = '`youtube.com.*v=([-_a-z0-9]+)`i';
    protected $html5WidgetTemplate = '<iframe class="youtube-player" type="text/html" width="%WIDTH%" height="%HEIGHT%" src="http://www.youtube.com/embed/%SOURCE_ID%" frameborder="0" webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe>';
}

class FancyMediaServiceYoutu extends FancyMediaServiceYoutube
{
    protected $id                  = 'youtu';
    protected $matcher             = '`youtu.be/([-_a-z0-9]+)`i';
}

// Vimeo.
class FancyMediaServiceVimeo extends FancyMediaService
{
    protected $id                  = 'vimeo';
    protected $matcher             = '`/([0-9]+)`';
    protected $html5WidgetTemplate = '<iframe src="http://player.vimeo.com/video/%SOURCE_ID%?portrait=0" width="%WIDTH%" height="%HEIGHT%" frameborder="0" webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe>';
}

// SoundCloud.
class FancyMediaServiceSoundcloud extends FancyMediaService
{
    protected $id                  = 'soundcloud';
    protected $matcher             = '`soundcloud.com/([-_a-z0-9]+/[-_a-z0-9]+)`i';
    protected $playerUrlTemplate   = 'http://player.soundcloud.com/player.swf?url=http://soundcloud.com/%SOURCE_ID%&amp;g=bb&amp;show_comments=false';
    protected $widgetHeight        = '81';
}

// Daylimotion.
class FancyMediaServiceDaylimotion extends FancyMediaService
{
    protected $id                  = 'dailymotion';
    protected $matcher             = '`video/([a-z0-9]+)_`i';
    protected $html5WidgetTemplate = '<iframe frameborder="0" width="%WIDTH%" height="%HEIGHT%" src="http://www.dailymotion.com/embed/video/%SOURCE_ID%?theme=none"></iframe>';

    protected $widgetWidth         = '640';
    protected $widgetHeight        = '360';
}

// Facebook.
class FancyMediaServiceFacebook extends FancyMediaService
{
    protected $id                  = 'facebook';
    protected $matcher             = '`facebook.com/.*video\.php\?v=([0-9]+)`i';
    protected $html5WidgetTemplate = '<iframe src="https://www.facebook.com/video/embed?video_id=%SOURCE_ID%" width="%WIDTH%" height="%HEIGHT%" frameborder="0"></iframe>';
}

// Rutube.
class FancyMediaServiceRutube extends FancyMediaService
{
    protected $id                  = 'rutube';
    protected $matcher             = '`/video/([a-z0-9]+)`i';
    protected $html5WidgetTemplate = '<iframe width="%WIDTH%" height="%HEIGHT%" src="http://rutube.ru/video/embed/%SOURCE_ID%" frameborder="0" webkitAllowFullScreen mozallowfullscreen allowfullscreen></iframe>';
}

// Vine.
class FancyMediaServiceVine extends FancyMediaService
{
    protected $id                  = 'vine';
    protected $matcher             = '`vine.co/v/([a-z0-9]+)`i';
    protected $html5WidgetTemplate = '<iframe class="vine-embed" src="https://vine.co/v/%SOURCE_ID%/embed/postcard" width="%WIDTH%" height="%HEIGHT%" frameborder="0"></iframe><script async src="//platform.vine.co/static/scripts/embed.js" charset="utf-8"></script>';

    protected $widgetWidth         = '480';
    protected $widgetHeight        = '480';

    public function getWidget($url)
    {
        $sourceId = $this->extractSourceId($url);

        if (is_null($sourceId)) {
            $playerCode = $this->getFailedPlayerCode($url);
        } else {
            $a1 = array('%SOURCE_ID%', '%WIDTH%', '%HEIGHT%');
            $a2 = array(forum_htmlencode($sourceId), $this->widgetWidth, $this->widgetHeight);

            $frameCode  = str_replace($a1, $a2, $this->html5WidgetTemplate);
            $playerCode = '<div class="fancy_video_tag_player">' . $frameCode . '</div>';
        }

        return $playerCode;
    }
}