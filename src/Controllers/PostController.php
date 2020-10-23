<?php

namespace App\Controllers;

use App\Entities\Video;

class PostController extends BaseController
{
    public function showAction(string $category, string $slug)
    {
        $content = Video::fetch($this->db, $slug, $category);

        // If no content, Error 404
        if (!$content) {
            return $this->app->notFound();
        }

        $content->updateVisitsCounter();

        $videos = Video::fetchRelated($this->db, $content->id, $category);

        $this->httpCache($content);

        return $this->app->view()->render(
            'content.phtml',
            [
                'nav' => 'news',
                'content' => $content,
                'videos' => $videos,
            ]
        );
    }
}
