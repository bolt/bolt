<?php

namespace Bolt\Storage\Database\Prefill;

use Bolt\Collection\ImmutableBag;
use Bolt\Filesystem\Exception\IOException;
use Bolt\Filesystem\FilesystemInterface;
use Bolt\Storage\Collection;
use Bolt\Storage\Entity;
use Bolt\Storage\Mapping\ContentType;
use Bolt\Storage\Repository\ContentRepository;
use Cocur\Slugify\Slugify;
use GuzzleHttp\Exception\RequestException;

/**
 * Create a generated set of pre-filled records for a ContentType.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class RecordContentGenerator
{
    /** @var ApiClient */
    private $apiClient;
    /** @var ImageClient */
    private $imageClient;
    /** @var string */
    private $contentTypeName;
    /** @var ContentRepository */
    private $repository;
    /** @var FilesystemInterface */
    private $filesystem;
    /** @var array */
    private $taxConfig;

    /** @var array */
    private $imageFiles;
    /** @var ImmutableBag */
    private $defaultTitles;
    /** @var array */
    private $createSummary;

    /**
     * Constructor.
     *
     * @param string              $contentTypeName
     * @param ApiClient           $apiClient
     * @param ContentRepository   $repository
     * @param FilesystemInterface $filesystem
     * @param array               $taxConfig
     * @param ImmutableBag        $defaultTitles
     */
    public function __construct(
        $contentTypeName,
        ApiClient $apiClient,
        ImageClient $imageClient,
        ContentRepository $repository,
        FilesystemInterface $filesystem,
        array $taxConfig,
        ImmutableBag $defaultTitles
    ) {
        $this->contentTypeName = $contentTypeName;
        $this->apiClient = $apiClient;
        $this->imageClient = $imageClient;
        $this->repository = $repository;
        $this->filesystem = $filesystem;
        $this->taxConfig = $taxConfig;
        $this->defaultTitles = $defaultTitles;
    }

    /** @var array */
    private $fieldMap = [
        // Boolean
        'checkbox'       => 'addBoolean',
        // Date/time
        'date'           => 'addDate',
        'datetime'       => 'addDate',
        // Numbers
        'float'          => 'addNumeric',
        'number'         => 'addNumeric',
        'integer'        => 'addNumeric',
        // String
        'text'           => 'addText',
        'templateselect' => 'addText',
        'file'           => 'addText',
        'slug'           => 'addText',
        'hidden'         => 'addText',
        'html'           => 'addText',
        'markdown'       => 'addText',
        'select'         => 'addText',
        'textarea'       => 'addText',
        // JSON arrays
        'filelist'       => 'addJson',
        'geolocation'    => 'addJson',
        'image'          => 'addJson',
        'imagelist'      => 'addJson',
        'oembed'         => 'addJson',
        'selectmultiple' => 'addJson',
        'templatefields' => 'addJson',
        'video'          => 'addJson',
    ];

    /**
     * Generate 'n' number of dummy records.
     *
     * @param int $count
     *
     * @return array
     */
    public function generate($count)
    {
        if (!is_int($count) || $count < 1) {
            throw new \InvalidArgumentException(sprintf('%s requires a value greater than 1.', __METHOD__));
        }

        $ownerIds = $this->getValidOwnerIds();

        for ($i = 0; $i < $count; $i++) {
            /** @var Entity\Content $entity */
            $entity = $this->repository->create([
                'datecreated'   => date('Y-m-d H:i:s', time() - rand(0, 365 * 24 * 60 * 60)),
                'datepublish'   => date('Y-m-d H:i:s', time() - rand(0, 365 * 24 * 60 * 60)),
                'datedepublish' => null,
                'status'        => 'published',
            ]);
            $ownerKey = array_rand($ownerIds, 1);
            $entity->setOwnerid($ownerIds[$ownerKey]);

            $this->fillEntity($entity);
        }
        $summary = $this->createSummary;
        $this->createSummary = null;

        return $summary;
    }

    /**
     * Return the name of the ContentType that entities are generated for.
     *
     * @return string
     */
    public function getContentTypeName()
    {
        return $this->contentTypeName;
    }

    /**
     * Create a single ContentType entity.
     *
     * @param Entity\Content $contentEntity
     */
    private function fillEntity(Entity\Content $contentEntity)
    {
        $contentType = $this->repository->getEntityManager()->getContentType($contentEntity->getContenttype());

        foreach ($contentType['fields'] as $field => $values) {
            $this->setFieldValue($contentEntity, $contentType, $field, $values);
        }

        // After we initially filled the content object, we get the title to set the slug.
        $slug = Slugify::create()->slugify($contentEntity->getTitle());
        $contentEntity->set('slug', $slug);

        // Add taxonomy
        $this->setTaxonomyCollection($contentEntity, $contentType);

        // Save it
        $this->repository->save($contentEntity);

        $taxonomies = null;
        /** @var Entity\Taxonomy $taxonomy */
        foreach ($contentEntity->getTaxonomy()->toArray() as $taxonomy) {
            $type = $taxonomy->getTaxonomytype();
            $taxonomies[$type][] = $taxonomy->getName();
        }

        $this->createSummary[] = [
            'title'    => $contentEntity->getTitle(),
            'taxonomy' => $taxonomies,
        ];
    }

    /**
     * @param Entity\Content    $contentEntity
     * @param array|Contenttype $contentType
     * @param string            $fieldName
     * @param array             $values
     */
    private function setFieldValue(Entity\Content $contentEntity, $contentType, $fieldName, array $values)
    {
        $type = $values['type'];
        if (!array_key_exists($type, $this->fieldMap)) {
            return;
        }
        call_user_func_array([$this, $this->fieldMap[$type]], [$contentEntity, $fieldName, $type, $contentType]);
    }

    /**
     * @param Entity\Content $contentEntity
     * @param string         $fieldName
     */
    private function addBoolean(Entity\Content $contentEntity, $fieldName)
    {
        $value = (bool) rand(0, 1);
        $contentEntity->set($fieldName, $value);
    }

    /**
     * @param Entity\Content $contentEntity
     * @param string         $fieldName
     * @param string         $type
     */
    private function addDate(Entity\Content $contentEntity, $fieldName, $type)
    {
        if ($type === 'date') {
            $contentEntity->set($fieldName, date('Y-m-d', time() - rand(-365 * 24 * 60 * 60, 365 * 24 * 60 * 60)));

            return;
        }
        // datetime
        $contentEntity->set($fieldName, date('Y-m-d H:i:s', time() - rand(-365 * 24 * 60 * 60, 365 * 24 * 60 * 60)));
    }

    /**
     * @param Entity\Content $contentEntity
     * @param string         $fieldName
     * @param string         $type
     */
    private function addNumeric(Entity\Content $contentEntity, $fieldName, $type)
    {
        if ($type === 'float') {
            $contentEntity->set($fieldName, rand(-1000, 1000) + (rand(0, 1000) / 1000));

            return;
        }
        // integer/number (number is deprecated)
        $contentEntity->set($fieldName, (int) (rand(-1000, 1000) + (rand(0, 1000) / 1000)));
    }

    /**
     * @param Entity\Content    $contentEntity
     * @param string            $fieldName
     * @param string            $type
     * @param array|ContentType $contentType
     */
    private function addText(Entity\Content $contentEntity, $fieldName, $type, $contentType)
    {
        if ($type === 'text') {
            if ($fieldName === 'title' && $this->defaultTitles->has($contentType['slug'])) {
                // Special case: if we're prefilling a 'blocks' ContentType add some
                // sensible titles to get started.
                $value = $this->getReservedTitle($contentType['slug']);
            } elseif (strpos($fieldName, 'link') !== false) {
                // Another special case: If the field name contains 'link', we guess it'll be used
                // as a link, so don't prefill it with "text", but leave it blank instead.
                $value = null;
            } else {
                $value = trim(strip_tags($this->apiClient->get('/1/veryshort')));
            }
            $contentEntity->set($fieldName, $value);

            return;
        } elseif (in_array($type, ['file', 'select', 'templateselect'])) {
            $contentEntity->set($fieldName, null);

            return;
        }

        // html, textarea, markdown
        if (in_array($fieldName, ['teaser', 'introduction', 'excerpt', 'intro', 'content'])) {
            $params = '/medium/decorate/link/1';
        } else {
            $params = '/medium/decorate/link/ol/ul/3';
        }

        $value = trim($this->apiClient->get($params));
        if ($type == 'markdown') {
            $value = strip_tags($value);
        }
        $contentEntity->set($fieldName, $value);
    }

    /**
     * @param Entity\Content $contentEntity
     * @param string         $fieldName
     * @param string         $type
     */
    private function addJson(Entity\Content $contentEntity, $fieldName, $type)
    {
        $contentType = $this->repository->getEntityManager()->getContentType($contentEntity->getContenttype());
        $placeholder = isset($contentType['fields'][$fieldName]['placeholder']) ? $contentType['fields'][$fieldName]['placeholder'] : null;
        $value = null;
        if ($type === 'image') {
            $value = $this->getRandomImage($type, $placeholder);
        } elseif ($type === 'imagelist') {
            for ($i = 1; $i <= 3; $i++) {
                $value[] = $this->getRandomImage($type, $placeholder);
            }
        } elseif ($type === 'filelist' || $type === 'templatefields') {
            $value = [];
        }

        $contentEntity->set($fieldName, $value);
    }

    /**
     * Add some random taxonomy entries on the record.
     *
     * @param Entity\Content    $contentEntity
     * @param array|ContentType $contentType
     */
    private function setTaxonomyCollection(Entity\Content $contentEntity, $contentType)
    {
        if (empty($contentType['taxonomy'])) {
            return;
        }
        /** @var Collection\Taxonomy $taxonomies */
        $taxonomies = $this->repository->getEntityManager()->createCollection(Entity\Taxonomy::class);

        foreach ($contentType['taxonomy'] as $key => $taxonomy) {
            $taxEntity = null;

            if (isset($this->taxConfig[$taxonomy]['options'])) {
                $options = $this->taxConfig[$taxonomy]['options'];
                $key = array_rand($options);

                $taxEntity = $this->getTaxonomyEntity($contentEntity, $taxonomy);
                $taxEntity->setSlug($key);
                $taxEntity->setName($options[$key]);
                $taxEntity->setSortorder(rand(1, 1000));

                $taxonomies->add($taxEntity);
            }

            if ($this->taxConfig[$taxonomy]['behaves_like'] === 'tags') {
                $tags = $this->getRandomTags(5);
                foreach ($tags as $tag) {
                    $taxEntity = $this->getTaxonomyEntity($contentEntity, $taxonomy);
                    $taxEntity->setSlug($tag);
                    $taxEntity->setName($tag);

                    $taxonomies->add($taxEntity);
                }
            }
        }

        $contentEntity->setTaxonomy($taxonomies);
    }

    /**
     * Get a base taxonomy entity object.
     *
     * @param Entity\Content $contentEntity
     * @param string         $taxonomy
     *
     * @return Entity\Taxonomy
     */
    private function getTaxonomyEntity(Entity\Content $contentEntity, $taxonomy)
    {
        return new Entity\Taxonomy([
            'content_id'   => $contentEntity->getId(),
            'contenttype'  => (string) $contentEntity->getContentType(),
            'taxonomytype' => $taxonomy,
        ]);
    }

    /**
     * Return an array of enabled system IDs.
     *
     * @return array
     */
    private function getValidOwnerIds()
    {
        $userRepo = $this->repository->getEntityManager()->getRepository(Entity\Users::class);
        $query = $userRepo->createQueryBuilder()
            ->select('id')
            ->where('enabled = :enabled')
            ->setParameter('enabled', true)
        ;
        $userRecords = $query->execute()->fetchAll(\PDO::FETCH_COLUMN);

        return $userRecords;
    }

    /**
     * Return an array of file names in the "files" filesystem.
     *
     * @return \Bolt\Filesystem\Handler\Image[]
     */
    private function getImageFiles()
    {
        if ($this->imageFiles === null) {
            // Get a list of images.
            $this->imageFiles = $this->filesystem
                ->find()
                ->in('files://')
                ->name('/\.jpe?g$/')
                ->name('*.png')
                ->toArray()
            ;
        }

        return $this->imageFiles;
    }

    /**
     * Get the title for a 'Block' ContentType. Check if the desired ones
     * aren't present in the database yet, and return them in order.
     *
     * @param string $contentTypeName
     *
     * @return string
     */
    private function getReservedTitle($contentTypeName)
    {
        $defaultTitles = $this->defaultTitles->get($contentTypeName);
        $title = array_shift($defaultTitles);
        $existing = $this->repository->findOneBy(['title' => $title]);

        if ($existing || $title === null) {
            $title = trim(strip_tags($this->apiClient->get('/1/veryshort')));
        }
        $this->defaultTitles->set($contentTypeName, $defaultTitles);

        return $title;
    }

    /**
     * Get an array of random tags
     *
     * @param int $count
     *
     * @return string[]
     */
    private function getRandomTags($count = 5)
    {
        $tags = ['action', 'adult', 'adventure', 'alpha', 'animals', 'animation', 'anime', 'architecture', 'art',
            'astronomy', 'baby', 'batshitinsane', 'biography', 'biology', 'book', 'books', 'business', 'business',
            'camera', 'cars', 'cats', 'cinema', 'classic', 'comedy', 'comics', 'computers', 'cookbook', 'cooking',
            'crime', 'culture', 'dark', 'design', 'digital', 'documentary', 'dogs', 'drama', 'drugs', 'education',
            'environment', 'evolution', 'family', 'fantasy', 'fashion', 'fiction', 'film', 'fitness', 'food',
            'football', 'fun', 'gaming', 'gift', 'health', 'hip', 'historical', 'history', 'horror', 'humor',
            'illustration', 'inspirational', 'internet', 'journalism', 'kids', 'language', 'law', 'literature', 'love',
            'magic', 'math', 'media', 'medicine', 'military', 'money', 'movies', 'mp3', 'murder', 'music', 'mystery',
            'news', 'nonfiction', 'nsfw', 'paranormal', 'parody', 'philosophy', 'photography', 'photos', 'physics',
            'poetry', 'politics', 'post-apocalyptic', 'privacy', 'psychology', 'radio', 'relationships', 'research',
            'rock', 'romance', 'rpg', 'satire', 'science', 'sciencefiction', 'scifi', 'security', 'self-help',
            'series', 'software', 'space', 'spirituality', 'sports', 'story', 'suspense', 'technology', 'teen',
            'television', 'terrorism', 'thriller', 'travel', 'tv', 'uk', 'urban', 'us', 'usa', 'vampire', 'video',
            'videogames', 'war', 'web', 'women', 'world', 'writing', 'wtf', 'zombies',
        ];

        shuffle($tags);

        $picked = array_slice($tags, 0, $count);

        return $picked;
    }

    /**
     * Get a random image.
     *
     * @param string      $type
     * @param string|null $placeholder
     *
     * @return array|null
     */
    private function getRandomImage($type, $placeholder)
    {
        $pathKey = $type === 'image' ? 'file' : 'filename';

        if ($placeholder && ($filename = $this->fetchPlaceholder($placeholder))) {
            return [$pathKey => $filename, 'title' => 'placeholder', 'alt' => 'placeholder'];
        }

        $images = $this->getImageFiles();
        if (empty($images)) {
            return null;
        }
        $imageName = array_rand($images);
        $image = $images[$imageName];
        $title = $image->getFilename($image->getExtension());
        $title = ucwords(str_replace('-', ' ', $title));

        return [$pathKey => $image->getPath(), 'title' => $title, 'alt' => $title];
    }

    /**
     * Attempt to fetch a placeholder image from a remote URL.
     *
     * @param string $placeholder
     *
     * @return array|bool
     */
    private function fetchPlaceholder($placeholder)
    {
        try {
            $image = $this->imageClient->get($placeholder);
        } catch (RequestException $e) {
            // We couldn't fetch the file, fall back to default behaviour
            return false;
        }

        $filename = sprintf('placeholder_%s.jpg', substr(md5(microtime()), 0, 12));

        try {
            $this->filesystem->put('files://' . $filename, $image);
        } catch (IOException $e) {
            // We couldn't save the file, fall back to default behaviour
            return false;
        }

        return $filename;
    }
}
