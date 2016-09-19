<?php

class SQLDownParser
{

    public $activeSystem;
    public $activeTable;
    public $activeColumns = [];
    public $systems       = [];

    public function parse($contents)
    {
        $this->contents = $contents;
        $this->stripComments()
            ->stripHeadings()
            ->explodeLines()
            ->stripEmptyLines();

        foreach ($this->contents as $line) {
            $lt = $this->detectLineType($line);
            call_user_func_array([$this, 'handleLine' . $lt], [$line]);
        }

        $this->newSystem();

        echo json_encode($this->systems, JSON_PRETTY_PRINT);
        echo "\n";
    }

    public function parseFile($filename)
    {
        return $this->parse(file_get_contents($filename));
    }

    public function stripComments()
    {
        $pattern        = '\/\*.[^\*]*\*\/';
        $this->contents = preg_replace("/{$pattern}/s", '', $this->contents);

        $pattern        = "\/\/.*";
        $this->contents = preg_replace("/{$pattern}/", '', $this->contents);

        return $this;
    }

    public function stripHeadings()
    {
        $pattern        = '^#.*';
        $this->contents = preg_replace("/{$pattern}/", '', $this->contents);
        return $this;
    }

    public function explodeLines()
    {
        $this->contents = explode(PHP_EOL, $this->contents);

        return $this;
    }

    public function stripEmptyLines()
    {
        $this->contents = array_values(array_filter($this->contents, function ($line) {
            return !empty($line);
        }));
        return $this;
    }

    public function detectLineType($line)
    {
        $count = substr_count($line, "\t");

        switch ($count) {
            case 0:
                return 'System';
                break;
            case 1:
                return 'Table';
                break;
            case 2:
                return 'Column';
                break;
        }
    }

    public function handleLineSystem($line)
    {
        $this->newSystem();
        $this->activeSystem = $line;
    }

    public function handleLineTable($line)
    {
        $this->newTable();
        $this->activeTable = trim($line);
    }

    public function handleLineColumn($line)
    {
        $l = trim($line);

        $parts = explode('=>', $l);
        $col   = array_shift($parts);
        $rel   = array_shift($parts);

        list($name, $type) = explode(' ', $col);

        $this->activeColumns[$name] = [
            'type' => $type,
            'rel'  => trim($rel),
        ];
    }

    public function newSystem()
    {

        if (empty($this->activeSystem)) {
            return;
        }

        $this->newTable();
        $this->activeSystem = '';
    }

    public function newTable()
    {
        if (empty($this->activeSystem) ||
            empty($this->activeTable)) {
            return;
        }

        if (!isset($this->systems[$this->activeSystem])) {
            $this->systems[$this->activeSystem] = [];
        }

        if (!isset($this->systems[$this->activeSystem][$this->activeTable])) {
            $this->systems[$this->activeSystem][$this->activeTable] = [];
        }

        $this->systems[$this->activeSystem][$this->activeTable] = $this->activeColumns;

        $this->activeTable   = '';
        $this->activeColumns = [];
    }

}

$parser = new SQLDownParser();
$parser->parseFile('example.erdwn');

function dd()
{
    call_user_func_array('var_dump', func_get_args());
    die();
}
