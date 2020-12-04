<?php

namespace App\Controllers;

use App\Entities\Video;

class CategoryController extends BaseController
{
    protected $videos;

    public function indexAction(string $category)
    {
        $this->videos = Video::fetchByCategory($this->db, $category);

        // If no content, Error 404
        if (empty($this->videos)) {
            return $this->app->notFound();
        }

        // die(var_dump($this->videos[0]->category->slug));

        $this->httpCache($this->videos);

        if (count($this->videos) > 2) {
            $totalVideos = count($this->videos) - 2;
            $multiple = $totalVideos % 3;
            $remainingVideos = 0;

            if ($multiple !== 0) {
                $remainingVideos = 3 - $multiple;
            }

            for ($i = 0; $i < $remainingVideos; ++$i) {
                $object = new \stdClass();

                array_push($this->videos, $object);
            }
        }

        return $this->app->view()->render(
            'category.phtml',
            [
                'nav' => 'news',
                'videos' => $this->videos,
            ]
        );
    }
}
