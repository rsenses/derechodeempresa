<?php

namespace App\Entities;

use Datetime;
use PDO;
use App\Services\MyPDO;

class Video
{
    const SAMPLE_RATE = 100;

    public $db;
    public $fiesta = false;
    public $options;
    public $category;
    public $url;

    public function __construct(MyPDO $db)
    {
        $this->db = $db;

        $this->title_txt = strip_tags(preg_replace("/<br\s?\/?>/", ' ', $this->title));

        $this->image_720 = str_replace('/', '/720_', $this->image);

        $this->date_formated = $this->getDateTimeFromFormat('Y-m-d H:i:s');
        $this->date_zulu = $this->getDateTimeFromFormat('Y-m-d\TH:i:s\Z');

        $this->category = $this->category();
        $this->tags = $this->tags();

        $this->options = $this->options();

        $this->url = empty($this->options['url']) ? "/{$this->category->slug}/{$this->url}" : $this->options['url'];
    }

    public function tags()
    {
        return Tag::fetchAll($this->db, $this->id);
    }

    public function category()
    {
        return Category::fetch($this->db, $this->id);
    }

    public function options()
    {
        if (!empty($this->options)) {
            return json_decode($this->options, true);
        }

        return null;
    }

    private function getDateTimeFromFormat($format)
    {
        $dateTime = DateTime::createFromFormat('Y-m-d H:i:s', $this->date);
        setlocale(LC_TIME, 'es_ES'); //only necessary if the locale isn't already set
        return strftime('%d %b. %Y', $dateTime->getTimestamp());
    }

    public static function fetch(MyPDO $db, string $slug, string $category)
    {
        $stmt = $db->run('
            SELECT v.id, v.title, v.subtitle, v.content, v.url, v.image, v.vimeo, v.twitter, v.facebook, v.description, v.date, v.updated_at, v.options, v.visits, a.name AS author_name, a.image AS author_image, a.link AS author_link, a.twitter AS author_twitter, a.position AS author_position
            FROM videos AS v
                JOIN section AS s ON v.id = s.content_id
                JOIN tags AS t ON t.id = s.tag_id
                LEFT JOIN author AS a ON v.author_id = a.author_id
            WHERE v.section = :section
            AND t.url = :category
            AND v.url = :slug
        ', [
            'section' => $GLOBALS['config']['web_slug'],
            'category' => $category,
            'slug' => $slug
        ]);

        $stmt->setFetchMode(PDO::FETCH_CLASS, 'App\Entities\Video', [$db]);
        return $stmt->fetch();
    }

    public static function fetchPopular(MyPDO $db, int $limit = 99)
    {
        $stmt = $db->run('
            SELECT v.id, v.title, v.subtitle, v.twitter, v.options, v.url, v.image, v.vertical, v.vimeo, v.date, v.updated_at, a.name AS author_name
            FROM videos AS v
                LEFT JOIN author AS a ON v.author_id = a.author_id
            WHERE v.section = :section
            AND v.active = 1
            ORDER BY v.visits DESC
            LIMIT :limit
            ', [
            'section' => $GLOBALS['config']['web_slug'],
            'limit' => $limit
        ]);

        return $stmt->fetchAll(PDO::FETCH_CLASS, 'App\Entities\Video', [$db]);
    }

    public static function fetchRelated(MyPDO $db, int $id, string $category)
    {
        try {
            $stmt = $db->run('
                SELECT v.id, v.title, v.url, v.image, v.options, v.vertical, v.vimeo, v.date, a.name AS author_name
                FROM videos AS v
                    JOIN section AS s ON v.id = s.content_id
                    JOIN tags AS t ON t.id = s.tag_id
                	LEFT JOIN author AS a ON v.author_id = a.author_id
                WHERE v.section = :section
                    AND t.url = :category
                    AND v.id != :id
                    AND v.active = 1
                GROUP BY v.id
                ORDER BY v.visits DESC, v.date DESC
                LIMIT 3;
            ', [
                'section' => $GLOBALS['config']['web_slug'],
                'category' => $category,
                'id' => $id,
            ]);

            return $stmt->fetchAll(PDO::FETCH_CLASS, 'App\Entities\Video', [$db]);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), 500);
        }
    }

    public static function fetchAll(MyPDO $db, int $limit = 99)
    {
        $args = [
            'section' => $GLOBALS['config']['web_slug'],
            'limit' => $limit
        ];

        $query = '
            SELECT v.id, v.title, v.subtitle, v.twitter, v.url, v.image, v.options, v.vertical, v.vimeo, v.date, v.updated_at, a.name AS author_name
            FROM videos AS v
                LEFT JOIN author AS a ON v.author_id = a.author_id
            WHERE v.section = :section
            AND v.active = 1
            ORDER BY v.date DESC
            LIMIT :limit
        ';

        $stmt = $db->run($query, $args);

        return $stmt->fetchAll(PDO::FETCH_CLASS, 'App\Entities\Video', [$db]);
    }

    public static function fetchByCategory(MyPDO $db, string $category = null, int $limit = 99)
    {
        $args = [
            'section' => $GLOBALS['config']['web_slug'],
            'category' => $category,
            'limit' => $limit
        ];

        $query = '
            SELECT v.id, v.title, v.subtitle, v.twitter, v.url, v.image, v.options, v.vertical, v.vimeo, v.date, v.updated_at, a.name AS author_name
            FROM videos AS v
                JOIN section AS s ON v.id = s.content_id
                JOIN tags AS t ON t.id = s.tag_id
                LEFT JOIN author AS a ON v.author_id = a.author_id
            WHERE v.section = :section
            AND v.active = 1
            AND t.url = :category
            ORDER BY v.date DESC
            LIMIT :limit
        ';

        $stmt = $db->run($query, $args);

        return $stmt->fetchAll(PDO::FETCH_CLASS, 'App\Entities\Video', [$db]);
    }

    public static function fetchByTag(MyPDO $db, string $tag, int $limit = 99)
    {
        $args = [
            'section' => $GLOBALS['config']['web_slug'],
            'tag' => $tag,
            'limit' => $limit
        ];

        $query = '
            SELECT v.id, v.title, v.subtitle, v.twitter, v.url, v.image, v.vertical, v.options, v.vimeo, v.date, v.updated_at, a.name AS author_name
            FROM videos AS v
                JOIN tagLinks AS s ON v.id = s.content_id
                JOIN tags AS t ON t.id = s.tag_id
                LEFT JOIN author AS a ON v.author_id = a.author_id
            WHERE v.section = :section
            AND v.active = 1
            AND t.url = :tag
            ORDER BY v.date DESC
            LIMIT :limit
        ';

        $stmt = $db->run($query, $args);

        return $stmt->fetchAll(PDO::FETCH_CLASS, 'App\Entities\Video', [$db]);
    }

    public function updateVisitsCounter()
    {
        // Add SAMPLE_RATE to visits counter
        if (mt_rand(1, self::SAMPLE_RATE) === 1) {
            $this->visits = $this->visits + self::SAMPLE_RATE;

            $this->db->prepare('UPDATE videos SET visits = ? WHERE id = ?')
                ->execute([$this->visits, $this->id]);
        }
    }
}
