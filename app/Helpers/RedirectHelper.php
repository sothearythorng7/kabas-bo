<?php

use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\URL;

if (! function_exists('redirectBackLevels')) {
    /**
     * Redirect back $levels back in history.
     *
     * @param int $levels Number of steps to go back (default 1)
     * @param string|null $fallback Fallback URL if not enough history
     * @return \Illuminate\Http\RedirectResponse
     */
    function redirectBackLevels(int $levels = 1, string $fallback = null)
    {
        $history = $_SESSION['url_history'] ?? []; // rempli par AJAX

        if (count($history) >= $levels) {
            $target = $history[count($history) - $levels];
        } else {
            $target = $fallback ?? url('/'); // url()->previous() n’a pas le hash
        }

        return redirect($target);
    }
}
