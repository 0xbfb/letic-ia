<?php

return [
    'target_chars' => (int) env('CHUNKING_TARGET_CHARS', 1200),
    'overlap_chars' => (int) env('CHUNKING_OVERLAP_CHARS', 200),
];
