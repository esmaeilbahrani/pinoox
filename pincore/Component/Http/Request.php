<?php
/**
 *      ****  *  *     *  ****  ****  *    *
 *      *  *  *  * *   *  *  *  *  *   *  *
 *      ****  *  *  *  *  *  *  *  *    *
 *      *     *  *   * *  *  *  *  *   *  *
 *      *     *  *    **  ****  ****  *    *
 * @author   Pinoox
 * @link https://www.pinoox.com/
 * @license  https://opensource.org/licenses/MIT MIT License
 */

namespace Pinoox\Component\Http;

use Illuminate\Support\Str;
use Pinoox\Component\Helpers\HelperArray;
use Pinoox\Component\Router\Collection;
use Pinoox\Component\Upload\FileUploader;
use Pinoox\Component\Validation\Factory as ValidationFactory;
use Pinoox\Component\Http\File\UploadedFile;
use Symfony\Component\HttpFoundation\FileBag;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request as RequestSymfony;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\RequestContext;
use Illuminate\Validation\Validator;

class Request extends RequestSymfony
{
    public InputBag $json;
    public ParameterBag $parameters;
    public bool $isContentJson = false;

    /**
     * @var \Closure|SessionInterface|null
     */
    public SessionInterface|\Closure|null $session;

    public function initialize(array $query = [], $request = [], array $attributes = [], array $cookies = [], array $files = [], array $server = [], $content = null): void
    {
        parent::initialize($query, $request, $attributes, $cookies, $files, $server, $content);
        $this->convertFiles();
        $this->initJsonData();
        $this->parameters = $this->attributes;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->fetchDataByKey($this->input()->all(), $key, $default);
    }

    public function getOne(string $key, mixed $default = null)
    {
        return HelperArray::parseParams(
            $this->all(),
            $key,
            $default,
        );
    }


    private function initJsonData(): void
    {
        $data = [];

        if (!empty($this->getContent())) {
            $data = (array)@json_decode($this->getContent(), true);
            $this->isContentJson = json_last_error() === JSON_ERROR_NONE;
        }

        $data = is_array($data) ? $data : [];
        $this->json = new InputBag($data);
    }

    private RequestContext $context;
    private ValidationFactory $validation;

    /**
     * get current Route
     *
     * @return array|null
     */
    public function route(): ?\Pinoox\Component\Router\Route
    {
        return @$this->attributes->get('_router');
    }

    /**
     * get current Collection
     *
     * @return Collection|null
     */
    public function collection(): Collection|null
    {
        return @$this->route()->getCollection();
    }

    public static function create(string $uri, string $method = 'GET', array $parameters = [], array $cookies = [], array $files = [], array $server = [], $content = null): static
    {
        $server = array_replace([
            'SERVER_NAME' => 'localhost',
            'SERVER_PORT' => 80,
            'HTTP_HOST' => 'localhost',
            'HTTP_USER_AGENT' => 'Pinoox',
            'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'HTTP_ACCEPT_LANGUAGE' => 'en-us,en;q=0.5',
            'HTTP_ACCEPT_CHARSET' => 'ISO-8859-1,utf-8;q=0.7,*;q=0.7',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_NAME' => '',
            'SCRIPT_FILENAME' => '',
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'REQUEST_TIME' => time(),
            'REQUEST_TIME_FLOAT' => microtime(true),
        ], $server);
      
        return parent::create($uri, $method, $parameters, $cookies, $files, $server, $content);
    }

    public function setBaseUrl(string $baseUrl): void
    {
        $this->baseUrl = $baseUrl;
    }

    public function setBasePath(string $basePath): void
    {
        $this->basePath = $basePath;
    }

    public function query($keys, $default = null, $removeNull = false): array
    {
        return HelperArray::parseParams(
            $this->query->all(),
            $keys,
            $default,
            $removeNull
        );
    }

    public function isXmlHttpRequest(): bool
    {
        if (parent::isXmlHttpRequest()) {
            return true;
        }

        if ($this->headers->has('HTTP_X_REQUESTED_WITH') && strtolower($this->headers->get('HTTP_X_REQUESTED_WITH')) === 'xmlhttprequest') {
            return true;
        }

        if (!empty($this->getContent())) {
            return true;
        }

        if ($this->headers->has('Origin')) {
            return true;
        }

        if ($this->server->has('CONTENT_TYPE') && str_contains($this->server->get('CONTENT_TYPE'), 'application/json')) {
            return true;
        }

        return false;
    }

    public function isXHR(): bool
    {
        return $this->isXmlHttpRequest();
    }

    public function queryOne($key, $default = null): mixed
    {
        return HelperArray::parseParam(
            $this->query->all(),
            $key,
            $default,
        );
    }

    public function request($keys, $default = null, $removeNull = false): array
    {
        return HelperArray::parseParams(
            $this->request->all(),
            $keys,
            $default,
            $removeNull
        );
    }

    public function requestOne($key, $default = null): mixed
    {
        return HelperArray::parseParam(
            $this->json->all(),
            $key,
            $default,
        );
    }

    public function parameters($keys, $default = null, $removeNull = false): array
    {
        return HelperArray::parseParams(
            $this->parameters->all(),
            $keys,
            $default,
            $removeNull
        );
    }

    public function parametersOne($key, $default = null): mixed
    {
        return HelperArray::parseParam(
            $this->parameters->all(),
            $key,
            $default,
        );
    }


    public function json($keys, $default = null, $removeNull = false): array
    {
        return HelperArray::parseParams(
            $this->json->all(),
            $keys,
            $default,
            $removeNull
        );
    }

    public function jsonOne($key, $default = null): mixed
    {
        return HelperArray::parseParam(
            $this->json->all(),
            $key,
            $default,
        );
    }

    public static function take(): static
    {
        return static::createFromGlobals();
    }

    public function setValidation(ValidationFactory $validation): void
    {
        $this->validation = $validation;
    }

    public function getValidation(): ValidationFactory
    {
        return $this->validation;
    }

    public function validate(array $rules, array $messages = [], array $attributes = []): array
    {
        return $this->validation($rules, $messages, $attributes)->validate();
    }

    public function validation(array $rules, array $messages = [], array $attributes = []): Validator
    {
        return $this->getValidation()->make($this->all(), $rules, $messages, $attributes);
    }

    public function all(?string $key = null): array
    {
        return [
            ...$this->attributes->all($key),
            ...$this->request->all($key),
            ...$this->query->all($key),
            ...$this->json->all($key),
            ...$this->files->all($key),
        ];
    }

    protected function convertUploadedFiles(array $files)
    {
        return array_map(function ($file) {
            if (is_null($file) || (is_array($file) && empty(array_filter($file)))) {
                return $file;
            }

            return is_array($file)
                ? $this->convertUploadedFiles($file)
                : UploadedFile::createFromBase($file);
        }, $files);
    }

    protected function convertFiles(): void
    {
        $this->files = new FileBag($this->convertUploadedFiles($this->files->all()));
    }

    public function file(string $key, mixed $default = null): UploadedFile
    {
        if ($this->has($key)) {
            return $this->fetchDataByKey($this->files->all(), $key, $default);
        }

        return $default;
    }

    public function store(string $key, $destination, $access = 'public', mixed $default = null): ?FileUploader
    {
        return $this->file($key, $default)?->store($destination, $access);
    }

    public function getContext(): RequestContext
    {
        if (empty($this->context)) {
            $this->context = new RequestContext();
            $this->context->setBaseUrl($this->getBaseUrl());
            $this->context->setPathInfo($this->getPathInfo());
            $this->context->setMethod($this->getMethod());
            $this->context->setHost($this->getHost());
            $this->context->setScheme($this->getScheme());
            $this->context->setHttpPort($this->isSecure() || null === $this->getPort() ? 80 : $this->getPort());
            $this->context->setHttpsPort($this->isSecure() && null !== $this->getPort() ? $this->getPort() : 443);
            $this->context->setQueryString($this->server->get('QUERY_STRING', ''));
        }
        return $this->context;
    }

    public function isJson(): bool
    {
        return Str::contains($this->headers->get('CONTENT_TYPE') ?? '', ['/json', '+json']);
    }

    public function input(): InputBag
    {
        if ($this->isJson()) {
            return $this->json;
        }

        return in_array($this->getRealMethod(), ['GET', 'HEAD']) ? $this->query : $this->request;
    }

    public function merge(array $input): static
    {
        $this->input()->add($input);

        return $this;
    }

    public function replace(array $input): static
    {
        $this->input()->replace($input);

        return $this;
    }

    protected function fetchDataByKey($array, $key, $default = null)
    {
        if (isset($array[$key]))
            return $array[$key];

        $keys = explode('.', $key);
        foreach ($keys as $k) {
            if (isset($array[$k]))
                $array = $array[$k];
            else
                return $default;
        }

        return $array;
    }

    public function filter(string $key, mixed $default = null, int $filter = \FILTER_DEFAULT, mixed $options = []): mixed
    {
        return $this->input()->filter($key, $default, $filter, $options);
    }

    public function has(string $key): bool
    {
        return $this->input()->has($key);
    }

    public function remove(string $key): static
    {
        $this->input()->remove($key);

        return $this;
    }

    public function __get($key)
    {
        return $this->input()->get($key);
    }

    public function __set(string $name, $value): void
    {
        $this->input()->set($name, $value);
    }

    public function set(string $key, mixed $value): static
    {
        $this->input()->set($key, $value);

        return $this;
    }
}