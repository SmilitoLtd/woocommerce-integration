<?php

namespace SmilitoLtd;

class Updater
{
    private const GITHUB_ACCOUNT = 'SmilitoLtd';
    private const GITHUB_REPO = 'woocommerce-integration';

    /**
     * @var string
     */
    private $githubAuthToken = '';

    /**
     * @var string
     */
    private $file;

    /**
     * @var array
     */
    private $plugin;

    /**
     * @var string
     */
    private $basename;

    /**
     * @var bool
     */
    private $active;

    /**
     * @var array|null
     */
    private $githubResponse = null;

    public function __construct(string $file)
    {
        $this->file = $file;
    }

    public function setup(): void
    {
        $this->registerHooks();
    }

    private function registerHooks(): void
    {
        add_action('admin_init', [$this, 'setPluginProperties']);
        add_filter('pre_set_site_transient_update_plugins', [$this, 'modifyTransient'], 10, 1);
        add_filter('plugins_api', [$this, 'pluginPopup'], 10, 3);
        add_filter('upgrader_post_install', [$this, 'afterInstall'], 10, 3);
        add_filter(
            'upgrader_pre_download',
            function () {
                add_filter('http_request_args', [$this, 'downloadPackage'], 15, 2);
                return false;
            }
        );
    }

    public function setPluginProperties(): void
    {
        $this->plugin = get_plugin_data($this->file);
        $this->basename = plugin_basename($this->file);
        $this->active = is_plugin_active($this->basename);
    }

    private function setGithubAuthToken(string $token): void
    {
        $this->githubAuthToken = $token;
    }

    /**
     * @return array|null
     */
    private function getRepositoryInfo(): ?array
    {
        if ($this->githubResponse !== null) {
            return $this->githubResponse;
        }

        $requestUrl = sprintf('https://api.github.com/repos/%s/%s/releases', self::GITHUB_ACCOUNT, self::GITHUB_REPO);

        $args = [];

        if ($this->githubAuthToken) {
            $args['headers']['Authorization'] = "Bearer {$this->githubAuthToken}";
        }

        $githubResponse = wp_remote_retrieve_body(wp_remote_get($requestUrl, $args));
        $response = json_decode($githubResponse, false);

        $this->githubResponse = $response;

        return $this->githubResponse;
    }

    /**
     * @return object|null
     */
    private function getLatestReleaseInfo()
    {
        $releases = $this->getRepositoryInfo();
        if (!is_array($releases) || count($releases) === 0) {
            return null;
        }
        $res = current($releases);
        if (is_object($res)) {
            return $res;
        }
        return null;
    }

    public function modifyTransient($transient)
    {
        if (!property_exists($transient, 'checked') || !is_array($transient->checked)) {
            return $transient;
        }

        if (!array_key_exists($this->basename, $transient->checked)) {
            return $transient;
        }

        $currentVersion = $transient->checked[$this->basename];

        $slug = current(explode('/', $this->basename));

        $plugin = new \stdClass();
        $plugin->id = $this->basename;
        $plugin->url = $this->plugin['PluginURI'];
        $plugin->slug = $slug;
        $plugin->new_version = $currentVersion;
        $plugin->plugin = $this->basename;

        $info = $this->getLatestReleaseInfo();
        if (!$info) {
            $transient->no_update[$this->basename] = $plugin;
            return $transient;
        }
        $latestVersion = $info->tag_name;
        $outOfDate = version_compare($latestVersion, $currentVersion, '>');

        if (!$outOfDate) {
            $transient->no_update[$this->basename] = $plugin;
            return $transient;
        }

        $plugin->new_version = $latestVersion;
        $plugin->package = $info->zipball_url;

        $transient->response[$this->basename] = $plugin;

        return $transient;
    }

    public function pluginPopup($result, $action, $args)
    {
        if (!empty($args->slug)) {
            if ($args->slug == current(explode('/', $this->basename))) {
                $info = $this->getLatestReleaseInfo();
                if (!$info) {
                    return $result;
                }

                $plugin = new \stdClass();
                $plugin->id = $this->basename;
                $plugin->name = $this->plugin["Name"];
                $plugin->slug = $this->basename;
                $plugin->version = $info->tag_name;
                $plugin->author = $this->plugin["AuthorName"];
                $plugin->author_profile = $this->plugin["AuthorURI"];
                $plugin->last_updated = $info->published_at;
                $plugin->homepage = $this->plugin["PluginURI"];
                $plugin->short_description = $this->plugin["Description"];
                $plugin->sections = [
                    'Description' => $this->plugin["Description"],
                    'Updates' => $info->body,
                ];
                $plugin->download_link = $info->zipball_url;

                return $plugin;
            }
        }
        return $result;
    }

    public function downloadPackage($args, $url)
    {
        if (null !== $args['filename']) {
            if ($this->githubAuthToken) {
                $args = array_merge($args, [
                    'headers' => [
                        'Authorization' => "token {$this->githubAuthToken}",
                    ],
                ]);
            }
        }

        remove_filter('http_request_args', [$this, 'download_package']);

        return $args;
    }

    public function afterInstall($response, $hook_extra, $result)
    {
        global $wp_filesystem;

        $install_directory = plugin_dir_path($this->file);
        $wp_filesystem->move($result['destination'], $install_directory);
        $result['destination'] = $install_directory;

        if ($this->active) {
            activate_plugin($this->basename);
        }

        return $result;
    }

}
