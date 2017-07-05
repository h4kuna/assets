<?php

namespace h4kuna\Assets;

abstract class AssetException extends \Exception {}

class DirectoryIsNotWriteableException extends AssetException {}

class DownloadFaildFromExternalUrlException extends AssetException {}

class CompareTokensException extends AssetException {}

class HeaderLastModifyException extends AssetException {}

class InvalidArgumentException extends AssetException {}

class InvalidStateException extends AssetException {}

class DuplicityAssetNameException extends AssetException {}

class FileNotFoundException extends AssetException {}