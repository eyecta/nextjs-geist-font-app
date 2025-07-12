<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php echo htmlspecialchars(SITE_NAME); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars(SITE_DESCRIPTION); ?>" />
    <meta name="robots" content="index, follow" />
    <link rel="stylesheet" href="assets/css/style.css" />
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION[CSRF_TOKEN_NAME] ?? '', ENT_QUOTES); ?>" />
</head>
<body>
<header>
    <h1><?php echo htmlspecialchars(SITE_NAME); ?></h1>
</header>
<main>
