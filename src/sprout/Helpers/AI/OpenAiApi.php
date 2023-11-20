<?php
namespace Sprout\Helpers\AI;

use Http\Discovery\Exception\NotFoundException;
use Kohana;
use Kohana_Exception;
use OpenAI;
use OpenAI\Exceptions\ErrorException;
use OpenAI\Exceptions\InvalidArgumentException;
use OpenAI\Exceptions\UnserializableResponse;
use OpenAI\Exceptions\TransporterException;
use OpenAI\Responses\Chat\CreateResponse as ChatCreateResponse;
use OpenAI\Responses\Images\CreateResponse as ImageCreateResponse;

/**
 * General helper tools for interacting with the OpenAI API
 */
class OpenAiApi extends AiApiBase
{

    const ENDPOINTS = [
        'chatCompletion' => 'Chat Completions',
        'imageGenerateSrc' => 'Image Generation - External URL',
        'imageGenerateBlob' => 'Image Generation - Image Data',
    ];


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


    /**
     *
     * @param string|array $prompt
     * @param array $config
     *
     * @return string|null
     *
     * @throws Kohana_Exception
     * @throws NotFoundException
     * @throws InvalidArgumentException
     * @throws ErrorException
     * @throws UnserializableResponse
     * @throws TransporterException
     */
    public static function chatCompletion(string|array $prompt, array $config = []): ?string
    {
        if (!is_array($prompt)) {
            $prompt = [[
                'role' => 'user',
                'content' => $prompt,
            ]];
        }
        $data = [
            'model' => $config['model'] ?? 'gpt-4',
            'messages' => $prompt,
            'max_tokens' => $config['max-tokens'] ?? 500,
        ];

        // Exceptions need catching by caller
        $key = Kohana::config('openai.secret_key');
        $client = OpenAI::client($key);

        /** @var ChatCreateResponse */
        $response = $client->chat()->create($data);

        return $response['choices'][0]['message']['content'] ?? '';
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
     *
     * @throws Kohana_Exception
     * @throws NotFoundException
     * @throws InvalidArgumentException
     * @throws ErrorException
     * @throws UnserializableResponse
     * @throws TransporterException
     */
    public static function imageGenerateSrc(string $prompt, array $config = []): ?string
    {
        $data = [
            'model' => $config['model'] ?? 'dall-e-2',
            'prompt' => $prompt,
            'response_format' => 'url',
        ];

        // Exceptions need catching by caller
        $key = Kohana::config('openai.secret_key');
        $client = OpenAI::client($key);

        /** @var ImageCreateResponse */
        $response = $client->images()->create($data);

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
        $data = [
            'prompt' => $prompt,
            'response_format' => 'b64_json',
        ];

        if (!empty($config['model'])) {
            $data['model'] = $config['model'];
        }

        // Exceptions need catching by caller
        $key = Kohana::config('openai.secret_key');
        $client = OpenAI::client($key);

        /** @var ImageCreateResponse */
        $response = $client->images()->create($data);

        return $response['data'][0]['b64_json'];
    }

}