<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use OpenApi\Generator;

class GenerateSwaggerDocs extends Command
{
  protected $signature = 'swagger:generate';
  protected $description = 'Generate Swagger documentation';

  public function handle()
  {
    $openapi = Generator::scan([base_path('app')]);
    file_put_contents(base_path('public/api-docs.json'), $openapi->toJson());
    $this->info('Swagger documentation generated!');
  }
}
