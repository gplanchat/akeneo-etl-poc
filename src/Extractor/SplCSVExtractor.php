<?php

namespace App\Extractor;

use Kiboko\Component\ETL\Extractor\ExtractorInterface;

class SplCSVExtractor implements ExtractorInterface
{
    /**
     * @var \SplFileObject
     */
    private $file;

    /**
     * @var string
     */
    private $delimiter;

    /**
     * @var string
     */
    private $enclosure;

    /**
     * @var string
     */
    private $escape;

    /**
     * @param \SplFileObject $file
     * @param string         $delimiter
     * @param string         $enclosure
     * @param string         $escape
     */
    public function __construct(
        \SplFileObject $file,
        string $delimiter = ',',
        string $enclosure = '"',
        string $escape = '\\'
    ) {
        $this->file = $file;
        $this->delimiter = $delimiter;
        $this->enclosure = $enclosure;
        $this->escape = $escape;
    }

    /**
     * @return \Iterator
     */
    public function extract(): \Iterator
    {
        if ($this->file->eof()) {
            return;
        }

        $columns = $this->file->fgetcsv($this->delimiter, $this->enclosure, $this->escape);
        $columnsCount = count($columns);

        $lineNumber = 0;
        while (!$this->file->eof()) {
            $line = $this->file->fgetcsv($this->delimiter, $this->enclosure, $this->escape);
            $lineColumnsCount = count($line);
            ++$lineNumber;

            if ($lineColumnsCount <> $columnsCount) {
                file_put_contents('php://stderr', strtr(
                    'The line %line% does not contain the same amount of columns than the header line provides (actually %actual%, expected %expected%).' . PHP_EOL,
                    [
                        '%line%' => $lineNumber,
                        '%actual%' => $lineColumnsCount,
                        '%expected%' => $columnsCount,
                    ]
                ), FILE_APPEND);
                continue;
            }

            yield array_combine($columns, $line);
        }
    }
}
