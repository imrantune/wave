<?php

namespace Wave\Plugins\ExcelImporter\Pages;

use Filament\Pages\Page;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Wave\Plugins\ExcelImporter\Imports\GenericImport;
use Psr\Log\LoggerInterface;


class ImportExcel extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-upload';
    protected static string $view = 'plugins.excel-importer.pages.import-excel';

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
        ]);

        $file = $request->file('file');
        $tableName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

        // Assuming you have a logger instance, e.g., from dependency injection
        $logger = app(LoggerInterface::class); // Laravel's service container
        try 
        {
            Excel::import(new GenericImport($tableName, $logger), $file);

            return redirect()->route('filament.pages.ImportExcel')
                         ->with('success', 'File imported successfully.');


        } catch (\Exception $e) {
            // Log the error for debugging
            $logger->error('Import Error: ' . $e->getMessage());
            dd( $e->getFile( ) ." : ".$e->getLine( ) ." : ".  'Import Error: ' .$e->getMessage());
            // Redirect with an error message
            return redirect()->route('filament.pages.ImportExcel')
                             ->withErrors(['file' => 'Failed to import file. Please check the log for more details.']);
        }
                         
    }
}

