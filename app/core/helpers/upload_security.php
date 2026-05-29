<?php

if (!function_exists('secure_uploaded_image')) {
    function secure_upload_harden_directory(string $uploadDir): void
    {
        $htaccess = rtrim($uploadDir, DIRECTORY_SEPARATOR . '/') . DIRECTORY_SEPARATOR . '.htaccess';
        if (!is_file($htaccess)) {
            @file_put_contents($htaccess, "php_flag engine off\nOptions -Indexes\n<FilesMatch \"\\.(php|phtml|php3|php4|php5|phar|htaccess)$\">\n    Deny from all\n</FilesMatch>\n");
        }
    }

    function secure_uploaded_image(array $file, string $uploadDir, string $relativePrefix, int $maxSize, string $namePrefix = 'img'): array
    {
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        $dangerousExtensions = ['php', 'phtml', 'phar', 'php3', 'php4', 'php5', 'htaccess'];
        $allowedMimes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];

        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return [false, null, 'Falha no upload.'];
        }

        $tmp = (string)($file['tmp_name'] ?? '');
        $originalName = (string)($file['name'] ?? '');
        $size = (int)($file['size'] ?? 0);

        if ($tmp === '' || !is_uploaded_file($tmp)) {
            return [false, null, 'Upload invalido.'];
        }

        if ($size <= 0 || $size > $maxSize) {
            return [false, null, 'Imagem invalida ou muito grande.'];
        }

        $baseName = basename($originalName);
        if ($baseName !== $originalName || str_contains($baseName, "\0")) {
            return [false, null, 'Nome de ficheiro invalido.'];
        }

        $extension = strtolower(pathinfo($baseName, PATHINFO_EXTENSION));
        if ($extension === '' || in_array($extension, $dangerousExtensions, true) || !in_array($extension, $allowedExtensions, true)) {
            return [false, null, 'Formato nao permitido. Usa JPG/PNG/WEBP.'];
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($tmp);
        if (!is_string($mime) || !isset($allowedMimes[$mime])) {
            return [false, null, 'MIME de imagem invalido.'];
        }

        $imageInfo = @getimagesize($tmp);
        if ($imageInfo === false || empty($imageInfo[0]) || empty($imageInfo[1])) {
            return [false, null, 'Ficheiro nao e uma imagem valida.'];
        }

        $extension = $mime === 'image/jpeg' && $extension === 'jpeg' ? 'jpeg' : $allowedMimes[$mime];

        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
            return [false, null, 'Nao foi possivel criar a pasta de uploads.'];
        }

        $realDir = realpath($uploadDir);
        if ($realDir === false || !is_dir($realDir)) {
            return [false, null, 'Pasta de uploads invalida.'];
        }
        secure_upload_harden_directory($realDir);

        $safePrefix = preg_replace('~[^a-z0-9_-]+~i', '-', strtolower($namePrefix));
        $safePrefix = trim((string)$safePrefix, '-');
        if ($safePrefix === '') {
            $safePrefix = 'img';
        }

        $filename = $safePrefix . '-' . bin2hex(random_bytes(16)) . '.' . $extension;
        $destination = $realDir . DIRECTORY_SEPARATOR . $filename;
        $destinationDir = dirname($destination);

        if (realpath($destinationDir) !== $realDir) {
            return [false, null, 'Destino de upload invalido.'];
        }

        if (!move_uploaded_file($tmp, $destination)) {
            return [false, null, 'Nao foi possivel guardar a imagem.'];
        }

        $relativePrefix = trim(str_replace('\\', '/', $relativePrefix), '/');
        $relativePath = ($relativePrefix !== '' ? $relativePrefix . '/' : '') . $filename;

        return [true, ['abs' => $destination, 'rel' => $relativePath, 'name' => $filename, 'mime' => $mime], null];
    }
}
