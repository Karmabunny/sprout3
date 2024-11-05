<?php
/*
 * Copyright (C) 2023 Karmabunny Pty Ltd.
 *
 * This file is a part of SproutCMS.
 *
 * SproutCMS is free software: you can redistribute it and/or modify it under the terms
 * of the GNU General Public License as published by the Free Software Foundation, either
 * version 2 of the License, or (at your option) any later version.
 *
 * For more information, visit <http://getsproutcms.com>.
 */
namespace Sprout\Helpers\AI;

use Exception;
use Kohana;
use OpenAI;
use GuzzleHttp\Client as GuzzleHttpClient;
use Http\Discovery\Exception\NotFoundException;
use OpenAI\Client;
use OpenAI\Exceptions\InvalidArgumentException;
use OpenAI\Responses\Chat\CreateResponse as ChatCreateResponse;
use OpenAI\Responses\Images\CreateResponse as ImageCreateResponse;
use Sprout\Helpers\AiLoggingTrait;
use Sprout\Helpers\OpenAiClientTrait;

/**
 * General helper tools for interacting with the OpenAI API
 */
class OpenAiApi implements AiApiInterface
{

    use AiLoggingTrait;


    use OpenAiClientTrait;


    /** @var array */
    private static $_last_response = [];


    const ENDPOINTS = [
        'chatCompletion' => 'Chat Completions',
        'imageGenerateSrc' => 'Image Generation - External URL',
        'imageGenerateBlob' => 'Image Generation - Image Data',
    ];


    /**
     * Items that can be created / stored / listed / deleted
     *
     * @see self::listItems and self::deleteItems
     * Also used in DB Tools
     */
    const ITEM_TYPES = [
        'files' => [
            'name' => 'Files',
            'description' => 'Files are used to store data that can be used in completions, such as documents, images, and more.',
        ],
        'assistants' => [
            'name' => 'Assistants',
            'description' => 'Assistants are used to aid in specific tasks, or drive highly customised chat bots.',
        ],
    ];


    /**
     * Create a new Open AI Client with the given API token.
     *
     * @param string $key
     * @param string|null $organization
     * @param int|null $timeout
     * @return Client
     * @throws NotFoundException
     */
    public static function createClient(string $key, ?string $organization = null, ?int $timeout = null): Client
    {
        $http_args = [];

        if ($timeout) {
            $https_args['timeout'] = $timeout;
        }

        return OpenAI::factory()
            ->withApiKey($key)
            ->withOrganization($organization)
            ->withHttpClient(new GuzzleHttpClient($http_args))
            ->withHttpHeader('OpenAI-Beta', 'assistants=v2')
            ->make();
    }


    /** @inheritdoc  */
    public function getEndpoints(): array
    {
        return self::ENDPOINTS;
    }


    /** @inheritdoc  */
    public function getUsable(): bool
    {
        $key = Kohana::config('openai.secret_key');
        return !empty($key);
    }


    /** @inheritdoc */
    public function getLastRequestCost(): mixed
    {
        $tokens = self::getTokensUsed();
        return $tokens['total_tokens'];
    }


    /** @inheritdoc */
    public function getRequestCostUnit(): string
    {
        return 'tokens';
    }


    /**
     * Get the last response from the API
     *
     * This can be used to extract token usage, finish reason or other data
     *
     * @return array
     */
    public static function getLastResponse(): array
    {
        return self::$_last_response;
    }


    /**
     * Get the number of tokens used in the last request
     *
     * @return array [completion_tokens => int, prompt_tokens => int, total_tokens => int]
     */
    public static function getTokensUsed(): array
    {
        $response = self::$_last_response;

        return $response['usage'] ?? [
            'completion_tokens' => 0,
            'prompt_tokens' => 0,
            'total_tokens' => 0,
        ];
    }


    /**
     *
     * @param string|array $prompt
     * @param array $config
     *
     * @return string|null
     */
    public static function chatCompletion(string|array $prompt, array $config = []): ?string
    {
        if (!is_array($prompt)) {
            $prompt = [[
                'role' => 'user',
                'content' => $prompt,
            ]];
        }

        // Optional default config load
        if (empty($config)) {
            $config = Kohana::config('openai.chat_completion') ?? [];
        }

        $data = [
            'model' => $config['model'] ?? 'chatgpt-4o-latest',
            'max_tokens' => $config['max_tokens'] ?? 500,
            'messages' => $prompt,
        ];

        if (!empty($config['response_format'])) {
            $data['response_format'] = $config['response_format'];
        }

        $url = 'chat.completion';
        self::logRequest(__function__, $url, $data);

        try {
            // Exceptions need catching by caller
            $key = Kohana::config('openai.secret_key');
            $client = self::getOverrideClient() ?? self::createClient($key);

            /** @var ChatCreateResponse */
            $response = $client->chat()->create($data);

        } catch (Exception $e) {
            Kohana::logException($e);
            self::logAndThrowException(__FUNCTION__, $url, $e);
            throw $e;
        }

        $response = $response->toArray();
        self::logResponse(__FUNCTION__, $url, $response);

        // Make the last response available for debugging
        self::$_last_response = $response;

        return $response['choices'][0]['message']['content'] ?? end($response['choices'])['delta']['content'] ?? '';
    }


    /**
     * Generate an image with Open AI and return a URL to the hosted file
     *
     * https://platform.openai.com/docs/api-reference/images/create
     *
     * @param string $prompt
     * @param array $config
     *
     * @return string|null Image src URL
     */
    public static function imageGenerateSrc(string $prompt, array $config = []): ?string
    {

        // Optional default config load
        if (empty($config)) {
            $config = Kohana::config('openai.chat_completion') ?? [];
        }

        $data = [
            'model' => $config['model'] ?? 'dall-e-3',
            'prompt' => $prompt,
            'response_format' => 'url',
        ];

        $url = 'images.create';
        self::logRequest(__function__, $url, $data);

        try {
            // Exceptions need catching by caller
            $key = Kohana::config('openai.secret_key');
            $client = OpenAI::client($key);

            /** @var ImageCreateResponse */
            $response = $client->images()->create($data);

        } catch (Exception $e) {
            Kohana::logException($e);
            self::logAndThrowException(__FUNCTION__, $url, $e);
            throw $e;
        }

        $response = $response->toArray();
        self::logResponse(__FUNCTION__, $url, $response);

        // Make the last response available for debugging
        self::$_last_response = $response;

        return $response['data'][0]['url'] ?? null;
    }


    /**
     * Generate an image with Open AI and return a blob of image data
     *
     * Output this with something like:
     * echo "<img src=\"data:image/jpeg;base64,{$response}\" style=\"max-width: 100%\">";
     *
     * https://platform.openai.com/docs/api-reference/images/create
     *
     * @param string $prompt
     * @param array $config
     *
     * @return string Image contents
     */
    public static function imageGenerateBlob(string $prompt, array $config = []): ?string
    {

        // Optional default config load
        if (empty($config)) {
            $config = Kohana::config('openai.chat_completion') ?? [];
        }

        $data = [
            'prompt' => $prompt,
            'response_format' => 'b64_json',
        ];

        if (!empty($config['model'])) {
            $data['model'] = $config['model'];
        }

        $url = 'images.create';
        self::logRequest(__function__, $url, $data);

        try {
            // Exceptions need catching by caller
            $key = Kohana::config('openai.secret_key');
            $client = OpenAI::client($key);

            /** @var ImageCreateResponse */
            $response = $client->images()->create($data);

        } catch (Exception $e) {
            Kohana::logException($e);
            self::logAndThrowException(__FUNCTION__, $url, $e);
            throw $e;
        }

        $response = $response->toArray();
        self::logResponse(__FUNCTION__, $url, $response);

        // Make the last response available for debugging
        self::$_last_response = $response;

        return $response['data'][0]['b64_json'];
    }


    /**
     * List items of a given type. Note that returns the first page only.
     *
     * @param string $type
     * @param null|array $config
     *
     * @return array The item list
     */
    public static function listItems(string $type, ?array $config = []): array
    {
        // Optional default config load
        if (empty($config)) {
            $config = Kohana::config('openai') ?? [];
        }

        if (!array_key_exists($type, self::ITEM_TYPES)) {
            throw new InvalidArgumentException("Invalid type: $type");
        }

        // Exceptions need catching by caller
        $key = $config['secret_key'];

        /** @var Client */
        $client = OpenAI::client($key);

        $response = match($type)  {
            'files' => $client->files()->list(),
            'assistants' => $client->assistants()->list(),
            default => throw new InvalidArgumentException("Invalid type: $type"),
        };

        return $response->toArray();
    }


    /**
     * Delete a specified set of items of a given type
     *
     * @param string $type
     * @param array $item_ids
     * @param null|array $config
     *
     * @return int The number deleted
     */
    public static function deleteItems(string $type, array $item_ids, ?array $config = null): int
    {
        // Optional default config load
        if (empty($config)) {
            $config = Kohana::config('openai') ?? [];
        }

        if (!array_key_exists($type, self::ITEM_TYPES)) {
            throw new InvalidArgumentException("Invalid type: $type");
        }

        // Exceptions need catching by caller
        $key = $config['secret_key'];

        /** @var Client */
        $client = OpenAI::client($key);

        $deleted = 0;
        foreach ($item_ids as $item_id) {
            $response = match($type) {
                'files' => $client->files()->delete($item_id),
                'assistants' => $client->assistants()->delete($item_id),
                default => throw new InvalidArgumentException("Invalid type: $type"),
            };

            if ($response['deleted']) {
                $deleted++;
            }
        }

        return $deleted;
    }

}