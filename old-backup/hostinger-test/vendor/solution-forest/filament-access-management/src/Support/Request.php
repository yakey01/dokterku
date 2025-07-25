<?php

namespace SolutionForest\FilamentAccessManagement\Support;

class Request
{
    public static function isAjaxRequest(?\Illuminate\Http\Request $request = null)
    {
        /** @var \Illuminate\Http\Request $request */
        $request = $request ?: request();

        return $request->ajax() && ! $request->pjax();
    }
}
