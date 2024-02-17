<?php
/*
 *  This file is a part of small-swoole-db
 *  Copyright 2023 - Sébastien Kus
 *  Under GNU GPL V3 licence
 */

namespace Small\SwooleDb\Core\Persistence;

use Small\SwooleDb\Core\Column;
use Small\SwooleDb\Core\Contract\PersistenceInterface;
use Small\SwooleDb\Core\Enum\ColumnType;
use Small\SwooleDb\Core\Table;
use Small\SwooleDb\Exception\FileNotFoundException;
use Small\SwooleDb\Exception\WrongFormatException;
use Small\SwooleDb\Registry\Enum\ParamType;
use Small\SwooleDb\Registry\ParamRegistry;

class AsJsonFile implements PersistenceInterface
{

    const FILE_EXTENSION = '.json';

    /**
     * Persist table
     * @param string $name
     * @param Table $table
     * @return $this
     */
    public function persist(string $name, Table $table): self
    {
        // Persist structure
        $array = [
            'name' => $name,
            'columns' => [],
            'rowMaxSize' => $table->getMaxSize(),
        ];

        foreach ($table->getColumns() as $column) {
            $array['columns'][] = [
                'name' => $column->getName(),
                'type' => $column->getType()->value,
                'size' => $column->getSize()
            ];
        }

        // Persist data
        $array['data'] = $this->exportDataToArray($table);

        file_put_contents($this->getFilename($name), json_encode($array));

        return $this;
    }

    /**
     * Load table from
     * @param string $name
     * @return Table
     * @throws FileNotFoundException
     * @throws WrongFormatException
     * @throws \Small\SwooleDb\Exception\MalformedTable
     * @throws \Small\SwooleDb\Exception\NotFoundException
     */
    public function load(string $name): Table
    {

        $this->checkFileExists($name);

        if (($content = file_get_contents($this->getFilename($name))) === false) {
            throw new \LogicException('Can\'t read tests');
        }

        $array = json_decode($content, true);

        if (!is_array($array)) {
            throw new WrongFormatException('File ' . $this->getFilename($name) . ' is not a json object');
        }

        if (empty($array)) {
            throw new WrongFormatException('File ' . $this->getFilename($name) . ' is not a json file');
        }

        if (!array_key_exists('rowMaxSize', $array)) {
            throw new WrongFormatException('File ' . $this->getFilename($name) .
                ' does\'nt contains rows size definition');
        }
        $table = new Table($name, $array['rowMaxSize']);

        if (!array_key_exists('columns', $array)) {
            throw new WrongFormatException('File ' . $this->getFilename($name) .
                ' does\'nt contains columns definition');
        }
        foreach ($array['columns'] as $key => $columnSpecifications) {
            if (!array_key_exists('name', $columnSpecifications)) {
                throw new WrongFormatException('Column name of column #' . $key . ' is missing');
            }

            if (!array_key_exists('type', $columnSpecifications)) {
                throw new WrongFormatException('Column type of column #' . $key . ' is missing');
            }

            if (!array_key_exists('size', $columnSpecifications)) {
                throw new WrongFormatException('Column size of column #' . $key . ' is missing');
            }

            $table->addColumn(
                new Column(
                    $columnSpecifications['name'],
                    ColumnType::from($columnSpecifications['type']),
                    $columnSpecifications['size']
                )
            );
        }

        $table->create();

        if (array_key_exists('data', $array)) {
            foreach ($array['data'] as $line) {
                $key = $line['_key'];
                unset($line['_key']);
                $table->set($key, $line);
            }
        }

        return $table;
    }

    /**
     * Get file path
     * @param string $name
     * @return string
     * @throws \Small\SwooleDb\Exception\NotFoundException
     */
    public function getFilename(string $name)
    {
        return $this->getDataDirname() . '/' .
            $name . self::FILE_EXTENSION;
    }

    /**
     * Get data dir
     * @return string
     * @throws FileNotFoundException
     * @throws \Small\SwooleDb\Exception\NotFoundException
     */
    public function getDataDirname(): string
    {

        $dirname = ParamRegistry::getInstance()->get(ParamType::varLibDir) . '/'
            . ParamRegistry::getInstance()->get(ParamType::dataDirName);
        if (!is_dir($dirname)) {
            @mkdir($dirname, 0755, true);
        }

        if (!is_dir($dirname)) {
            throw new FileNotFoundException('Data directory not found, impossible to create it');
        }

        return $dirname;

    }

    /**
     * Check file exists
     * @param string $name
     * @return void
     * @throws FileNotFoundException
     * @throws \Small\SwooleDb\Exception\NotFoundException
     */
    private function checkFileExists(string $name): void
    {

        if (!file_exists($this->getFilename($name))) {
            throw new FileNotFoundException('File ' . $this->getFilename($name) . ' does\'nt exists');
        }

    }

    /**
     * Export table data to array
     * @param Table $table
     * @return mixed[][]
     */
    private function exportDataToArray(Table $table): array
    {

        $data = [];
        $table->rewind();
        while ($table->valid()) {
            $value = [];
            foreach ($table->current()->getData() as $key => $item) {
                if (in_array($key, Column::FORBIDDEN_NAMES)) {
                    throw new \LogicException('Column name ' . $key . ' is forbidden');
                }
                $value[$key] = $item;
            }
            $value['_key'] = $table->key();

            $data[] = $value;
            $table->next();
        }

        return $data;

    }

}