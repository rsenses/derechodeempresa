<?php

namespace App\Controllers;

use App\Entities\Video;

class HomeController extends BaseController
{
    const HOME_VIDEO_LIMIT = 13;

    protected $videos;

    public function indexAction()
    {
        $this->videos = Video::fetchAll($this->db, self::HOME_VIDEO_LIMIT);

        $this->httpCache($this->videos);

        return $this->app->view()->render(
            'home.phtml',
            [
                'nav' => 'home',
                'videos' => $this->videos,
            ]
        );
    }
}
