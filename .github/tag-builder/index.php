<?php

use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

require 'vendor/autoload.php';

const ALPINE_DISTRO_NAME = 'alpine';
const DEBIAN_DISTRO_NAME = 'debian';
const AVAILABLE_DISTRO = [
    ALPINE_DISTRO_NAME,
    DEBIAN_DISTRO_NAME,
];

const DISTRO_DIRECTORY_PATH_PATTERN = '%s/*/*/';
const DISTRO_TAG_FORMATS = [
    ALPINE_DISTRO_NAME => '%s/php:%s-%s%s',
    DEBIAN_DISTRO_NAME => '%s/php:%s-%s-%s',
];
const SPECIFIC_TAG_FORMAT = '%s/php:%s';

const DOCKERFILE = 'Dockerfile';
const IMAGE_KEY = 'image';
const TAGS_KEY = 'tags';

const SPECIFIC_PHP_TAGS_FILE_PATH = './.github/tag-builder/specific-php-tags.yml';

$organisationName = getenv('ORGANISATION');

$finder = new Finder();
$parser = new Yaml();

$specificTagsConfig = $parser->parseFile(SPECIFIC_PHP_TAGS_FILE_PATH);

$result = [];
foreach ($finder->in(getDistroDirectoryPathPattern())->files()->name(DOCKERFILE) as $file) {
    $result[] = buildMatrix($file, $organisationName, $specificTagsConfig);
}

echo json_encode($result);

/**
 * @param SplFileInfo $file
 * @param string $organisationName
 * @param array $specificTagsConfig
 *
 * @return array
 */
function buildMatrix(SplFileInfo $file, string $organisationName, array $specificTagsConfig): array
{
    $imagePath = $file->getPathname();
    $tags = buildTags($imagePath, $organisationName, $specificTagsConfig);

    if ($tags == []) {
        return [];
    }

    return [
        IMAGE_KEY => $imagePath,
        TAGS_KEY => $tags,
    ];
}

/**
 * @param string $imagePath
 * @param string $organisationName
 * @param array $specificTagsConfig
 *
 * @return array
 */
function buildTags(string $imagePath, string $organisationName, array $specificTagsConfig): array
{
    list($distroName, $distroVersion, $phpVersion, $fileName) = explode('/', $imagePath);

    $tags = [];
    $distroTagFormat = DISTRO_TAG_FORMATS[$distroName] ?? '%s/php:%s-%s-%s';

    $tags[] = sprintf($distroTagFormat, $organisationName, $phpVersion, $distroName, $distroVersion);

    if (!array_key_exists($imagePath, $specificTagsConfig)) {
        return $tags;
    }

    foreach ($specificTagsConfig[$imagePath] as $specificPhpVersion) {
        $tags[] = sprintf(SPECIFIC_TAG_FORMAT, $organisationName, $specificPhpVersion);
    }

    return $tags;
}

/**
 * @return array
 */
function getDistroDirectoryPathPattern(): array
{
    $result = [];

    foreach (AVAILABLE_DISTRO as $distroName) {
        $result[] = sprintf(DISTRO_DIRECTORY_PATH_PATTERN, $distroName);
    }

    return $result;
}
