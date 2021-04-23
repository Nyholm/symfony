<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Translation\Bridge\Lokalise\Provider;

use Psr\Log\LoggerInterface;
use Symfony\Component\Translation\Exception\ProviderException;
use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Translation\Provider\ProviderInterface;
use Symfony\Component\Translation\TranslatorBag;
use Symfony\Component\Translation\TranslatorBagInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @author Mathieu Santostefano <msantostefano@protonmail.com>
 *
 * @experimental in 5.3
 *
 * In Lokalise:
 *  * Filenames refers to Symfony's translation domains;
 *  * Keys refers to Symfony's translation keys;
 *  * Translations refers to Symfony's translated messages
 */
final class LokaliseProvider implements ProviderInterface
{
    private $projectId;
    private $client;
    private $loader;
    private $logger;
    private $defaultLocale;
    private $endpoint;

    public function __construct(string $projectId, HttpClientInterface $client, LoaderInterface $loader, LoggerInterface $logger, string $defaultLocale, string $endpoint)
    {
        $this->projectId = $projectId;
        $this->client = $client;
        $this->loader = $loader;
        $this->logger = $logger;
        $this->defaultLocale = $defaultLocale;
        $this->endpoint = $endpoint;
    }

    public function __toString(): string
    {
        return sprintf('%s://%s', LokaliseProviderFactory::SCHEME, $this->endpoint);
    }

    public function getName(): string
    {
        return LokaliseProviderFactory::SCHEME;
    }

    /**
     * {@inheritdoc}
     */
    public function write(TranslatorBagInterface $translatorBag): void
    {
        $this->createKeysWithTranslations($translatorBag);
    }

    public function read(array $domains, array $locales): TranslatorBagInterface
    {
        $translatorBag = new TranslatorBag();
        $translations = $this->exportFiles($locales, $domains);

        foreach ($translations as $locale => $files) {
            foreach ($files as $filename => $content) {
                $intlDomain = $domain = str_replace('.xliff', '', $filename);
                $suffixLength = \strlen(MessageCatalogue::INTL_DOMAIN_SUFFIX);
                if (\strlen($domain) > $suffixLength && false !== strpos($domain, MessageCatalogue::INTL_DOMAIN_SUFFIX, -$suffixLength)) {
                    $intlDomain .= MessageCatalogue::INTL_DOMAIN_SUFFIX;
                }

                if (\in_array($intlDomain, $domains, true)) {
                    $translatorBag->addCatalogue($this->loader->load($content['content'], $locale, $intlDomain));
                } else {
                    $this->logger->info(sprintf('The translations fetched from Lokalise under the filename "%s" does not match with any domains of your application.', $filename));
                }
            }
        }

        return $translatorBag;
    }

    public function delete(TranslatorBagInterface $translatorBag): void
    {
        $catalogue = $translatorBag->getCatalogue($this->defaultLocale);

        if (!$catalogue) {
            $catalogue = $translatorBag->getCatalogues()[0];
        }

        $keysIds = [];
        foreach ($catalogue->all() as $messagesByDomains) {
            foreach ($messagesByDomains as $domain => $messages) {
                $keysToDelete = [];
                foreach ($messages as $message) {
                    $keysToDelete[] = $message;
                }
                $keysIds += $this->getKeysIds($keysToDelete, $domain);
            }
        }

        $response = $this->client->request('DELETE', sprintf('/projects/%s/keys', $this->projectId), [
            'json' => ['keys' => $keysIds],
        ]);

        if (200 !== $response->getStatusCode()) {
            throw new ProviderException(sprintf('Unable to delete keys from Lokalise: "%s".', $response->getContent(false)), $response);
        }
    }

    /**
     * Lokalise API recommends sending payload in chunks of up to 500 keys per request.
     *
     * @see https://app.lokalise.com/api2docs/curl/#transition-create-keys-post
     */
    private function createKeysWithTranslations(TranslatorBag $translatorBag): void
    {
        $keys = [];
        $catalogue = $translatorBag->getCatalogue($this->defaultLocale);

        if (!$catalogue) {
            $catalogue = $translatorBag->getCatalogues()[0];
        }

        foreach ($translatorBag->getDomains() as $domain) {
            foreach ($catalogue->all($domain) as $key => $message) {
                $keys[] = [
                    'key_name' => $key,
                    'platforms' => ['web'],
                    'filenames' => [
                        'web' => $this->generateLokaliseFilenameFromDomain($domain),
                        // There is a bug in Lokalise with "Per platform key names" option enabled,
                        // we need to provide a filename for all platforms.
                        'ios' => null,
                        'android' => null,
                        'other' => null,
                    ],
                    'translations' => array_map(function ($catalogue) use ($key, $domain) {
                        return [
                            'language_iso' => $catalogue->getLocale(),
                            'translation' => $catalogue->get($key, $domain),
                        ];
                    }, $translatorBag->getCatalogues()),
                ];
            }
        }

        $chunks = array_chunk($keys, 500);

        foreach ($chunks as $chunk) {
            $response = $this->client->request('POST', sprintf('/projects/%s/keys', $this->projectId), [
                'json' => ['keys' => $chunk],
            ]);

            if (200 !== $response->getStatusCode()) {
                throw new ProviderException(sprintf('Unable to add keys and translations to Lokalise: "%s".', $response->getContent(false)), $response);
            }
        }
    }

    /**
     * @see https://app.lokalise.com/api2docs/curl/#transition-download-files-post
     */
    private function exportFiles(array $locales, array $domains): array
    {
        $response = $this->client->request('POST', sprintf('/projects/%s/files/export', $this->projectId), [
            'json' => [
                'format' => 'symfony_xliff',
                'original_filenames' => true,
                'directory_prefix' => '%LANG_ISO%',
                'filter_langs' => array_values($locales),
                'filter_filenames' => array_map([$this, 'generateLokaliseFilenameFromDomain'], $domains),
            ],
        ]);

        $responseContent = $response->toArray(false);

        if (406 === $response->getStatusCode()
            && 'No keys found with specified filenames.' === $responseContent['error']['message']
        ) {
            return [];
        }

        if (200 !== $response->getStatusCode()) {
            throw new ProviderException(sprintf('Unable to export translations from Lokalise: "%s".', $response->getContent(false)), $response);
        }

        return $responseContent['files'];
    }

    private function getKeysIds(array $keys, string $domain): array
    {
        $response = $this->client->request('GET', sprintf('/projects/%s/keys', $this->projectId), [
            'query' => [
                'filter_keys' => $keys,
                'filter_filenames' => $this->generateLokaliseFilenameFromDomain($domain),
            ],
        ]);

        $responseContent = $response->toArray(false);

        if (200 !== $response->getStatusCode()) {
            throw new ProviderException(sprintf('Unable to get keys ids from Lokalise: "%s".', $response->getContent(false)), $response);
        }

        return array_reduce($responseContent['keys'], function ($keysIds, array $keyItem) {
            $keysIds[] = $keyItem['key_id'];

            return $keysIds;
        }, []);
    }

    private function generateLokaliseFilenameFromDomain(string $domain): string
    {
        return sprintf('%s.xliff', $domain);
    }
}
