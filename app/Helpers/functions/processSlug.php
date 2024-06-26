<?php

function process_slug($slug) {
    $slugWithoutSuffix = strtok($slug, '_');
    return $slugWithoutSuffix;
}
