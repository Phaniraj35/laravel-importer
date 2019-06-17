<?php namespace Ladybird\import;

use Illuminate\Http\Request;

interface ImportInterface
{
    public function parseImport(Request $request);
    public function processImport(Request $request);
    public function getDbCols();
}
