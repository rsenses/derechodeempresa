<?php

namespace App\Controllers;

use App\Entities\Video;
use App\Entities\Tag;

class TagController extends BaseController
{
    protected $videos;

    public function indexAction(string $tag)
    {
        $this->videos = Video::fetchByTag($this->db, $tag);

        // If no content, Error 404
        if (empty($this->videos)) {
            return $this->app->notFound();
        }

        $this->httpCache($this->videos);

        return $this->app->view()->render(
            'tag.phtml',
            [
                'nav' => 'news',
                'tag' => Tag::fetch($this->db, $tag),
                'videos' => $this->videos,
            ]
        );
    }
}
