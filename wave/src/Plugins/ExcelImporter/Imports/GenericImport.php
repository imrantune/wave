<?php

namespace Wave\Plugins\ExcelImporter\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Psr\Log\LoggerInterface;

class GenericImport implements ToCollection
{
    protected $tableName;
    protected $report = [];
    protected $logger;

    public function __construct($tableName, LoggerInterface $logger)
    {
        $tableName = preg_replace('/[^a-zA-Z0-9_]/', '_', $tableName);
        $this->tableName = $tableName;
        $this->logger = $logger;
    }

   

    public function collection(Collection $rows)
    {
        // Start a transaction
        DB::beginTransaction();

        // Enable query logging
        DB::enableQueryLog();

        try {
            $header = $rows->first()->toArray();
            
            // Validate headers to ensure they are not empty
            $header = array_filter($header, function ($column) {
                return !empty(trim($column));
            });

            if (empty($header)) {
                throw new \Exception("The header row is empty or invalid.");
            }

            // Check if the main table exists, if not create it
            if (!Schema::hasTable($this->tableName)) {
                Schema::create($this->tableName, function (Blueprint $table) use ($header) {
                    $table->increments('id');
                    foreach ($header as $column) {
                        $column = preg_replace('/[^a-zA-Z0-9_]/', '_', $column);
                        $table->string($column)->nullable();
                    }
                    $table->timestamps();
                });
                // Initialize the report structure
                $this->report['main_table'] = $this->tableName;
                $this->report['columns_count'] = count($header);
                $this->report['subtables'] = [];
            } else {
                $this->report['columns_count'] = count($header);
            }

            $recordsInserted = 0;

            foreach ($rows as $key => $row) {
                if ($key == 0) {
                    continue; // Skip header row
                }

                $data = [];
                foreach ($header as $index => $column) {
                    $column = preg_replace('/[^a-zA-Z0-9_]/', '_', $column);
                    $value = isset($row[$index]) ? $row[$index] : null;

                    // If header is missing, generate default column name
                    if (empty(trim($column))) {
                        $column = $this->tableName . '_' . $this->getDefaultColumnName($index);
                    }

                    if (strpos($value, ',') !== false) {
                        $subValues = explode(',', $value);
                        $subTableName = $this->tableName . '_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $column);
                        if (!Schema::hasTable($subTableName)) {
                            Schema::create($subTableName, function (Blueprint $table) {
                                $table->increments('id');
                                $table->string('value');
                            });
                            $this->report['subtables'][$subTableName] = [
                                'columns' => ['value'],
                                'records_inserted' => 0,
                            ];
                        }

                        foreach ($subValues as $subValue) {
                            $subValueId = DB::table($subTableName)->insertGetId(['value' => $subValue]);
                            $data[$column] = $subValueId;
                            // Increment record count for the subtable
                            $this->report['subtables'][$subTableName]['records_inserted']++;
                        }
                    } else {
                        // Check for null values
                        if (is_null($value) && !$this->isNullableColumn($column)) {
                            $this->logger->warning("Null value found for non-nullable column: $column");
                            $hasNullValues = true;
                            continue; // Skip adding this column to the data array
                        }

                        $data[$column] = $value; 
                    }
                }

                DB::table($this->tableName)->insert($data);
                $recordsInserted++;
            }

            // Update the report with the number of records inserted into the main table
            $this->report['records_inserted'] = $recordsInserted;

            // Commit the transaction
            DB::commit();

            // Log the report
            $this->logReport();
        } catch (PDOException $e){

            dd($e->getMessage());

        } catch (\Exception $e) {
            // Rollback the transaction if something goes wrong
            DB::rollBack();
            // Log the error with Monolog
            dd($e->getMessage());
            app(LoggerInterface::class)->error('Import Error: ' . $e->getMessage());
            throw $e; // Re-throw the exception for further handling
        } finally {
            // Dump the query log after all operations
            //dd(DB::getQueryLog());
        }
    }


    protected function isNullableColumn($column)
    {
        // Check if the column is nullable in the database schema
        return Schema::hasColumn($this->tableName, $column) && Schema::getColumnType($this->tableName, $column) === 'string'; // Adjust as needed based on your schema
    }

    protected function getDefaultColumnName($index)
    {
        // Convert index to column letter (A, B, C, ...)
        $letter = chr(65 + $index);
        return "column_" . $letter; // e.g., column_A, column_B, ...
    }

    protected function logReport()
    {
        // Log the import report using Monolog
        $this->logger->info("Import Report for Table: {$this->report['main_table']}");
        $this->logger->info("Total Columns: {$this->report['columns_count']}");
        $this->logger->info("Records Inserted into Main Table: {$this->report['records_inserted']}");
        foreach ($this->report['subtables'] as $subTable => $details) {
            $this->logger->info("Subtable: $subTable");
            $this->logger->info(" - Columns: " . implode(', ', $details['columns']));
            $this->logger->info(" - Records Inserted: {$details['records_inserted']}");
        }
    }
}
