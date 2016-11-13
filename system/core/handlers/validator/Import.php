<?php

/**
 * @package GPL Cart core
 * @author Iurii Makukh <gplcart.software@gmail.com>
 * @copyright Copyright (c) 2015, Iurii Makukh
 * @license https://www.gnu.org/licenses/gpl.html GNU/GPLv3
 */

namespace core\handlers\validator;

use core\classes\Csv;
use core\classes\Request;
use core\models\File as ModelsFile;
use core\models\Import as ModelsImport;
use core\handlers\validator\Base as BaseValidator;

/**
 * Provides methods to validate import data
 */
class Import extends BaseValidator
{

    /**
     * CSV class instance
     * @var \core\classes\Csv $csv
     */
    protected $csv;

    /**
     * Request class instance
     * @var \core\classes\Request
     */
    protected $request;

    /**
     * Import model instance
     * @var \core\models\Import $import
     */
    protected $import;

    /**
     * File model instance
     * @var \core\models\File $file
     */
    protected $file;

    /**
     * Constructor
     * @param Csv $csv
     * @param Request $request
     * @param ModelsImport $import
     * @param ModelsFile $file
     */
    public function __construct(Csv $csv, Request $request,
            ModelsImport $import, ModelsFile $file)
    {
        parent::__construct();

        $this->csv = $csv;
        $this->file = $file;
        $this->import = $import;
        $this->request = $request;
    }

    /**
     * Performs full import data validation
     * @param array $submitted
     */
    public function import(array &$submitted, array $options = array())
    {
        $this->validateOperationImport($submitted);
        $this->validateFileImport($submitted);
        $this->validateCsvHeaderImport($submitted);

        return empty($this->errors) ? true : $this->errors;
    }

    /**
     * Validates an operation
     * @param array $submitted
     * @return boolean|null
     */
    protected function validateOperationImport(array &$submitted)
    {
        if (isset($submitted['operation_id'])) {
            $submitted['operation'] = $this->import->getOperation($submitted['operation_id']);
        }

        if (empty($submitted['operation'])) {
            $this->errors['operation'] = $this->language->text('Object @name does not exist', array(
                '@name' => $this->language->text('Operation')));
            return false;
        }

        return true;
    }

    /**
     * Validates a source file
     * @param array $submitted
     * @return boolean
     */
    protected function validateFileImport(array &$submitted)
    {
        $this->validateFilePathImport($submitted);
        $this->validateFileUploadImport($submitted);
    }

    /**
     * Validates a relative file path
     * @param array $submitted
     * @return boolean|null
     */
    protected function validateFilePathImport(array &$submitted)
    {
        if (!isset($submitted['path'])) {
            return null; // The file probably will be uploaded via UI, stop here
        }

        $filepath = GC_FILE_DIR . "/{$submitted['path']}";

        if (is_readable($filepath)) {
            $submitted['filepath'] = $filepath;
            $submitted['filesize'] = filesize($filepath);
            return true;
        }

        $this->errors['file'] = $this->language->text('Object @name does not exist', array(
            '@name' => $this->language->text('File')));
        return false;
    }

    /**
     * Validates a uploaded file
     * @param array $submitted
     * @return boolean|null
     */
    protected function validateFileUploadImport(array &$submitted)
    {
        if (isset($submitted['filepath'])) {
            return null; // The filepath already defined by a relative path
        }

        $file = $this->request->file('file');

        if (empty($file)) {
            $this->errors['file'] = $this->language->text('@field is required', array(
                '@field' => $this->language->text('File')
            ));
            return false;
        }

        $result = $this->file->setUploadPath('private/import')
                ->setHandler('csv')
                ->upload($file);

        if ($result !== true) {
            $this->errors['file'] = $result;
            return false;
        }

        $filepath = $this->file->getUploadedFile();
        $submitted['filepath'] = $filepath;
        $submitted['filesize'] = filesize($filepath);
        return true;
    }

    /**
     * Validates CSV header
     * @param array $submitted
     * @return boolean
     */
    public function validateCsvHeaderImport(array &$submitted)
    {
        if (!empty($this->errors)) {
            return null; // Abort on existing errors
        }

        $header = $submitted['operation']['csv']['header'];
        $delimiter = $this->import->getCsvDelimiter();

        $real_header = $this->csv->setFile($submitted['filepath'])
                ->setHeader($header)
                ->setDelimiter($delimiter)
                ->getHeader();

        $header_id = reset($header);
        $real_header_id = reset($real_header);

        if ($header_id !== $real_header_id || array_diff($header, $real_header)) {
            $this->errors['file'] = $this->language->text('Wrong header. Required columns: @format', array(
                '@format' => implode(' | ', $header)));
            return false;
        }

        return true;
    }

}
