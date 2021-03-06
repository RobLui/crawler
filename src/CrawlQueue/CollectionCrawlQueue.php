<?php

namespace Spatie\Crawler\CrawlQueue;

use Spatie\Crawler\CrawlUrl;
use Tightenco\Collect\Support\Collection;
use Spatie\Crawler\Exception\UrlNotFoundByIndex;

class CollectionCrawlQueue implements CrawlQueue
{
    /** @var \Tightenco\Collect\Support\Collection */
    public $urls;

    /** @var \Tightenco\Collect\Support\Collection */
    public $pendingUrls;

    public function __construct()
    {
        $this->urls = collect();

        $this->pendingUrls = collect();
    }

    public function add(CrawlUrl $url): CrawlQueue
    {
        if ($this->has($url)) {
            return $this;
        }

        $this->urls->push($url);

        $url->setId($this->urls->keys()->last());
        $this->pendingUrls->push($url);

        return $this;
    }

    public function hasPendingUrls(): bool
    {
        return (bool) $this->pendingUrls->count();
    }

    /**
     * @param int $id
     *
     * @return \Spatie\Crawler\CrawlUrl|null
     */
    public function getUrlById(int $id): CrawlUrl
    {
        if (! isset($this->urls->values()[$id])) {
            throw new UrlNotFoundByIndex("#{$id} crawl url not found in collection");
        }

        return $this->urls->values()[$id];
    }

    public function hasAlreadyBeenProcessed(CrawlUrl $url): bool
    {
        return ! $this->contains($this->pendingUrls, $url) && $this->contains($this->urls, $url);
    }

    public function markAsProcessed(CrawlUrl $crawlUrl)
    {
        $this->pendingUrls = $this->pendingUrls
            ->reject(function (CrawlUrl $crawlUrlItem) use ($crawlUrl) {
                return (string) $crawlUrlItem->url === (string) $crawlUrl->url;
            });
    }

    /**
     * @param CrawlUrl|\Psr\Http\Message\UriInterface|string $crawlUrl
     *
     * @return bool
     */
    public function has($crawlUrl): bool
    {
        if (! $crawlUrl instanceof CrawlUrl) {
            $crawlUrl = CrawlUrl::create($crawlUrl);
        }

        if ($this->contains($this->urls, $crawlUrl)) {
            return true;
        }

        return false;
    }

    private function contains(Collection $collection, CrawlUrl $searchCrawlUrl): bool
    {
        foreach ($collection as $crawlUrl) {
            if ((string) $crawlUrl->url === (string) $searchCrawlUrl->url) {
                return true;
            }
        }

        return false;
    }

    /** @return \Spatie\Crawler\CrawlUrl|null */
    public function getFirstPendingUrl()
    {
        return $this->pendingUrls->first();
    }
}
