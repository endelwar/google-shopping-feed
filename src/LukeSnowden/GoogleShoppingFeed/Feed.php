<?php namespace LukeSnowden\GoogleShoppingFeed;

use SimpleXMLElement;
use LukeSnowden\GoogleShoppingFeed\Item;
use Gregwar\Cache\Cache;

class Feed
{
    /**
     * [$namespace description]
     * @var string
     */
    protected $namespace = 'http://base.google.com/ns/1.0';

    /**
     * [$version description]
     * @var string
     */
    protected $version = '2.0';

    /**
     * [$items Stores the list of items for the feed]
     * @var Item[]
     */
    private $items = array();

    /**
     * [$channelCreated description]
     * @var boolean
     */
    private $channelCreated = false;

    /**
     * [$feed The base for the feed]
     * @var SimpleXMLElement
     */
    private $feed = null;

    /**
     * [$title description]
     * @var string
     */
    private $title = '';

    /**
     * [$cacheDir description]
     * @var string
     */
    private $cacheDir = 'cache';

    /**
     * [$description description]
     * @var string
     */
    private $description = '';

    /**
     * [$link description]
     * @var string
     */
    private $link = '';

    /**
     * Feed constructor.
     */
    public function __construct()
    {
        $this->feed = new SimpleXMLElement('<rss xmlns:g="' . $this->namespace . '" version="' . $this->version . '"></rss>');
    }

    /**
     * @param string $title
     */
    public function title($title)
    {
        $this->title = (string)$title;
    }

    /**
     * @param string $description
     */
    public function description($description)
    {
        $this->description = (string)$description;
    }

    /**
     * @param string $link
     */
    public function link($link)
    {
        $this->link = (string)$link;
    }

    /**
     * [channel description]
     */
    private function channel()
    {
        if (!$this->channelCreated) {
            $channel = $this->feed->addChild('channel');
            $channel->addChild('title', $this->title);
            $channel->addChild('link', $this->link);
            $channel->addChild('description', $this->description);
            $this->channelCreated = true;
        }
    }

    /**
     * @return \LukeSnowden\GoogleShoppingFeed\Item
     */
    public function createItem()
    {
        $this->channel();
        $item = new Item;
        $index = 'index_' . sha1(uniqid('GoogleShoppingFeed\Item', true) . microtime());
        $this->items[$index] = $item;
        $item->setIndex($index);

        return $item;
    }

    /**
     * @param string $index
     */
    public function removeItemByIndex($index)
    {
        unset($this->items[$index]);
    }

    /**
     * @param string $value
     * @return string
     */
    public function standardiseSizeVariant($value)
    {
        return $value;
    }

    /**
     * @param string $value
     * @return string
     */
    public function standardiseColourVariant($value)
    {
        return $value;
    }

    /**
     * @param string $group
     * @return bool|string
     */
    public function isVariant($group)
    {
        if (preg_match("#^\s*colou?rs?\s*$#is", trim($group))) {
            return 'color';
        }
        if (preg_match("#^\s*sizes?\s*$#is", trim($group))) {
            return 'size';
        }
        if (preg_match("#^\s*materials?\s*$#is", trim($group))) {
            return 'material';
        }

        return false;
    }

    /**
     * [addItemsToFeed description]
     */
    private function addItemsToFeed()
    {
        foreach ($this->items as $item) {
            $feedItemNode = $this->feed->channel->addChild('item');
            foreach ($item->nodes() as $itemNode) {
                if (is_array($itemNode)) {
                    foreach ($itemNode as $node) {
                        $feedItemNode->addChild($node->get('name'), $node->get('value'), $node->get('_namespace'));
                    }
                } else {
                    $itemNode->attachNodeTo($feedItemNode);
                }
            }
        }
    }

    /**
     * @return array
     * @throws \Gregwar\Cache\InvalidArgumentException
     */
    public function categories()
    {
        $cache = new Cache;
        $cache->setCacheDirectory($this->cacheDir);
        $data = $cache->getOrCreate('google-feed-taxonomy.txt', array('max-age' => '860400'), function () {
            return file_get_contents("http://www.google.com/basepages/producttype/taxonomy.en-GB.txt");
        });

        return explode("\n", trim($data));
    }

    /**
     * @param string $selected
     * @return string
     */
    public function categoriesAsSelect($selected = '')
    {
        $categories = $this->categories();
        unset($categories[0]);
        $select = '<select name="google_category">';
        $select .= '<option value="">Please select a Google Category</option>';
        foreach ($categories as $category) {
            $select .= '<option ' . ($category == $selected ? 'selected' : '') . ' name="' . $category . '">' . $category . '</option>';
        }
        $select .= '</select>';

        return $select;
    }

    /**
     * @param bool $output
     * @return string
     */
    public function asRss($output = false)
    {
        @ob_end_clean();
        $this->addItemsToFeed();
        $data = html_entity_decode($this->feed->asXml());
        if ($output) {
            header('Content-Type: application/xml; charset=utf-8');
            die($data);
        }

        return $data;
    }

    /**
     * [removeLastItem description]
     */
    public function removeLastItem()
    {
        array_pop($this->items);
    }
}
