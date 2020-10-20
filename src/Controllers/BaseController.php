<?php

namespace App\Controllers;

use flight\Engine;
use Symfony\Component\Templating\Helper\SlotsHelper;
use App\Entities\Video;

class BaseController
{
    protected $app;
    protected $db;

    public function __construct(Engine $app)
    {
        $this->app = $app;

        $this->db = $this->app->db();

        // Template Improvements
        $this->app->view()->set(new SlotsHelper());

        $this->app->view()->setEscaper('path', function (string $value = null) {
            return htmlspecialchars($value, ENT_QUOTES);
        });

        $this->app->view()->setEscaper('url', function (string $value = null) {
            return urlencode($value);
        });
    }

    /**
     * Normalize de content, call the proper functions based on it
     *
     * @param mixed $content
     * @return void
     */
    protected function httpCache($content)
    {
        if ($content instanceof Video) {
            $this->httpCacheSingleContent($content);
        } else {
            $this->httpCacheMultipleContent($content);
        }
    }

    /**
     * Set etga and lastmodified headers for a single content
     *
     * @param Video $content
     * @return void
     */
    private function httpCacheSingleContent(Video $content)
    {
        // Set HTTP Caché
        $this->app->lastModified(strtotime($content->updated_at));
        $this->app->etag('"' . md5($content->id . strtotime($content->updated_at)) . '"', 'weak');
    }

    /**
     * Set etag and lastmodified headers for an array of contents
     *
     * @param array $posts
     * @return void
     */
    private function httpCacheMultipleContent(array $posts)
    {
        $etag = '';
        $lastModified = 0;

        foreach ($posts as $content) {
            $etag .= $content->id;

            $lastModified = $lastModified > $content->updated_at ? $lastModified : $content->updated_at;
        }

        // Set HTTP Caché
        $this->app->lastModified(strtotime($lastModified));
        $this->app->etag('"' . md5($etag) . '"', 'weak');
    }
}
