<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ScaffoldDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:scaffold-database';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tables = DB::select('SHOW TABLES');

        foreach ($tables as $tableObj) {
            $table = array_values((array)$tableObj)[0];

            // Ignorar tabelas do Laravel
            if (in_array($table, ['migrations', 'password_resets', 'failed_jobs'])) {
                continue;
            }

            $this->generateForTable($table);
        }
    }

    protected function generateForTable($table)
    {
        $modelName = ucfirst($table);
        $controllerName = $modelName . 'Controller';

        // Criar controller
        $this->call('make:controller', [
            'name' => $controllerName,
            '--resource' => true
        ]);

        // Criar model
        $this->call('make:model', [
            'name' => $modelName
        ]);

        // Criar request
        $this->call('make:request', [
            'name' => $modelName . 'Request'
        ]);

        // Criar páginas Inertia
        $this->generateInertiaPages($modelName);

        // Criar rotas
        $this->appendRoutes($table, $controllerName);

        $this->info("Scaffold criado para tabela: $table");
    }

    protected function generateInertiaPages($model)
    {
        $path = resource_path("js/Pages/$model");

        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }

        file_put_contents("$path/Index.jsx", $this->reactIndexTemplate($model));
        file_put_contents("$path/Create.jsx", $this->reactCreateTemplate($model));
        file_put_contents("$path/Edit.jsx", $this->reactEditTemplate($model));
    }

    protected function appendRoutes($table, $controller)
    {
        $route = "Route::resource('$table', \\App\\Http\\Controllers\\$controller::class);\n";

        file_put_contents(
            base_path('routes/web.php'),
            $route,
            FILE_APPEND
        );
    }

    protected function reactIndexTemplate($model)
    {
        return <<<TSX
            import { Link } from '@inertiajs/react';

            interface ${model}Item {
            id: number;
            [key: string]: any;
            }

            interface Props {
            items: ${model}Item[];
            }

            export default function Index({ items }: Props) {
            return (
                <div>
                <h1>$model</h1>

                <Link href="/$model/create">Criar Novo</Link>

                <ul>
                    {items.map((i) => (
                    <li key={i.id}>
                        {i.id}
                        <Link href={\`/$model/\${i.id}/edit\`}>Editar</Link>
                    </li>
                    ))}
                </ul>
                </div>
            );
            }
            TSX;
    }
}
