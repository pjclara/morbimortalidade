<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ScaffoldDatabase extends Command
{
    protected $signature = 'app:scaffold-database';
    protected $description = 'Gera Models, Controllers, Requests, Rotas e Páginas Inertia TSX dinamicamente a partir da DB';

    public function handle()
    {
        $tables = DB::select('SHOW TABLES');

        foreach ($tables as $tableObj) {
            $table = array_values((array)$tableObj)[0];

            if (in_array($table, [
                'migrations',
                'failed_jobs',
                'password_resets',
                'personal_access_tokens'
            ])) {
                continue;
            }

            $this->info("Gerando scaffold para tabela: $table");

            // LER COLUNAS E FOREIGN KEYS
            $columns = $this->getColumns($table);
            $foreignKeys = $this->getForeignKeys($table);

            // MODEL EM SINGULAR
            $model = Str::singular(Str::studly($table));

            // GERAR MODEL COM FILLABLE + RELAÇÕES
            $this->generateModel($model, $columns, $foreignKeys);

            // GERAR CONTROLLER
            $this->generateController($model);

            // GERAR REQUEST
            $this->generateRequest($model);

            // GERAR ROTAS
            $this->appendRoutes($table, $model);

            // GERAR PÁGINAS INERTIA (TSX)
            $this->generateInertiaPages($model, $columns, $foreignKeys);
        }

        $this->info("Scaffolding completo!");
    }


    protected function getColumns(string $table): array
    {
        $database = DB::getDatabaseName();

        $columns = DB::table('INFORMATION_SCHEMA.COLUMNS')
            ->select('COLUMN_NAME as name', 'DATA_TYPE as type')
            ->where('TABLE_SCHEMA', $database)
            ->where('TABLE_NAME', $table)
            ->get()
            ->toArray();

        return array_map(fn($col) => [
            'name' => $col->name,
            'type' => $col->type,
        ], $columns);
    }


    protected function generateModel(string $model, array $columns, array $foreignKeys)
    {
        // Gerar o model base via Artisan
        $this->call('make:model', [
            'name' => $model
        ]);

        // Criar lista de fillable
        $fillable = [];

        foreach ($columns as $col) {
            if (in_array($col['name'], ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                continue;
            }

            $fillable[] = "'{$col['name']}'";
        }

        $fillableString = implode(', ', $fillable);

        // Caminho do model
        $modelPath = app_path("Models/$model.php");
        $content = file_get_contents($modelPath);

        // Inserir fillable dentro da classe
        $content = preg_replace(
            '/class ' . $model . ' extends Model\s*\{/',
            "class $model extends Model\n{\n    protected \$fillable = [$fillableString];\n",
            $content
        );

        // -----------------------------
        // GERAR RELAÇÕES BELONGS TO
        // -----------------------------
        $relations = "";

        foreach ($foreignKeys as $fk) {
            $refModel = Str::singular(Str::studly($fk['ref_table']));
            $methodName = Str::camel(Str::singular($fk['ref_table']));

            $relations .= <<<PHP

    public function $methodName()
    {
        return \$this->belongsTo($refModel::class, '{$fk['column']}', '{$fk['ref_column']}');
    }

PHP;
        }

        // -----------------------------
        // GERAR RELAÇÕES HAS MANY
        // -----------------------------
        $database = DB::getDatabaseName();

        $reverseFks = DB::table('INFORMATION_SCHEMA.KEY_COLUMN_USAGE')
            ->select('TABLE_NAME', 'COLUMN_NAME')
            ->where('TABLE_SCHEMA', $database)
            ->where('REFERENCED_TABLE_NAME', Str::plural(Str::snake($model)))
            ->get();

        foreach ($reverseFks as $rfk) {
            $childModel = Str::singular(Str::studly($rfk->TABLE_NAME));
            $methodName = Str::camel(Str::plural($rfk->TABLE_NAME));

            $relations .= <<<PHP

    public function $methodName()
    {
        return \$this->hasMany($childModel::class, '{$rfk->COLUMN_NAME}', 'id');
    }

PHP;
        }

        // Inserir relações no Model
        $content = preg_replace(
            '/}\s*$/',
            $relations . "\n}",
            $content
        );

        file_put_contents($modelPath, $content);
    }



    protected function generateController(string $model)
    {
        $this->call('make:controller', [
            'name' => "{$model}Controller",
            '--resource' => true
        ]);
    }

    protected function getForeignKeys(string $table): array
    {
        $database = DB::getDatabaseName();

        $fks = DB::table('INFORMATION_SCHEMA.KEY_COLUMN_USAGE')
            ->select('COLUMN_NAME', 'REFERENCED_TABLE_NAME', 'REFERENCED_COLUMN_NAME')
            ->where('TABLE_SCHEMA', $database)
            ->where('TABLE_NAME', $table)
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->get();

        $result = [];

        foreach ($fks as $fk) {
            $result[] = [
                'column' => $fk->COLUMN_NAME,
                'ref_table' => $fk->REFERENCED_TABLE_NAME,
                'ref_column' => $fk->REFERENCED_COLUMN_NAME,
            ];
        }

        return $result;
    }


    protected function generateRequest(string $model)
    {
        $this->call('make:request', [
            'name' => "{$model}Request"
        ]);
    }

    protected function appendRoutes(string $table, string $model)
    {
        $controller = "{$model}Controller";
        $route = "Route::resource('$table', \\App\\Http\\Controllers\\$controller::class);\n";

        file_put_contents(
            base_path('routes/web.php'),
            $route,
            FILE_APPEND
        );
    }

    protected function generateInertiaPages(string $model, array $columns)
    {
        $path = resource_path("js/Pages/$model");

        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }

        file_put_contents("$path/Index.tsx", $this->reactIndexTemplate($model, $columns));
        file_put_contents("$path/Create.tsx", $this->reactCreateTemplate($model, $columns));
        file_put_contents("$path/Edit.tsx", $this->reactEditTemplate($model, $columns));
    }

    protected function mapTypeToInput(string $type): string
    {
        return match ($type) {
            'integer', 'bigint', 'smallint' => 'number',
            'boolean' => 'checkbox',
            'text' => 'textarea',
            'date', 'datetime' => 'date',
            default => 'text',
        };
    }

    protected function mapTypeToTs(string $type): string
    {
        return match ($type) {
            'integer', 'bigint', 'smallint' => 'number',
            'boolean' => 'boolean',
            default => 'string',
        };
    }

    protected function reactIndexTemplate(string $model, array $columns)
    {
        $headers = "";
        $cells = "";

        foreach ($columns as $col) {
            $headers .= "          <th>{$col['name']}</th>\n";
            $cells   .= "          <td>{i.{$col['name']}}</td>\n";
        }

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

      <table>
        <thead>
          <tr>
$headers          </tr>
        </thead>
        <tbody>
          {items.map(i => (
            <tr key={i.id}>
$cells              <td>
                <Link href={\`/$model/\${i.id}/edit\`}>Editar</Link>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}
TSX;
    }

    protected function reactCreateTemplate(string $model, array $columns)
    {
        $fieldsTs = "";
        $fieldsForm = "";

        foreach ($columns as $col) {
            if ($col['name'] === 'id') continue;

            $tsType = $this->mapTypeToTs($col['type']);
            $inputType = $this->mapTypeToInput($col['type']);

            $fieldsTs .= "  {$col['name']}: $tsType;\n";

            if ($inputType === 'textarea') {
                $fieldsForm .= <<<TSX
      <textarea
        value={data.{$col['name']}}
        onChange={e => setData('{$col['name']}', e.target.value)}
      />
TSX;
            } elseif ($inputType === 'checkbox') {
                $fieldsForm .= <<<TSX
      <input
        type="checkbox"
        checked={data.{$col['name']}}
        onChange={e => setData('{$col['name']}', e.target.checked)}
      />
TSX;
            } else {
                $fieldsForm .= <<<TSX
      <input
        type="$inputType"
        value={data.{$col['name']}}
        onChange={e => setData('{$col['name']}', e.target.value)}
      />
TSX;
            }
        }

        return <<<TSX
import { useForm } from '@inertiajs/react';

interface ${model}Form {
$fieldsTs}

export default function Create() {
  const { data, setData, post } = useForm<${model}Form>({
$fieldsTs  });

  function submit(e: React.FormEvent) {
    e.preventDefault();
    post('/$model');
  }

  return (
    <form onSubmit={submit}>
$fieldsForm
      <button type="submit">Criar</button>
    </form>
  );
}
TSX;
    }

    protected function reactEditTemplate(string $model, array $columns)
    {
        $fieldsTs = "";
        $fieldsForm = "";

        foreach ($columns as $col) {
            $tsType = $this->mapTypeToTs($col['type']);
            $inputType = $this->mapTypeToInput($col['type']);

            $fieldsTs .= "  {$col['name']}: $tsType;\n";

            if ($inputType === 'textarea') {
                $fieldsForm .= <<<TSX
      <textarea
        value={data.{$col['name']}}
        onChange={e => setData('{$col['name']}', e.target.value)}
      />
TSX;
            } elseif ($inputType === 'checkbox') {
                $fieldsForm .= <<<TSX
      <input
        type="checkbox"
        checked={data.{$col['name']}}
        onChange={e => setData('{$col['name']}', e.target.checked)}
      />
TSX;
            } else {
                $fieldsForm .= <<<TSX
      <input
        type="$inputType"
        value={data.{$col['name']}}
        onChange={e => setData('{$col['name']}', e.target.value)}
      />
TSX;
            }
        }

        return <<<TSX
import { useForm } from '@inertiajs/react';

interface ${model}Form {
$fieldsTs}

export default function Edit({ item }: { item: ${model}Form }) {
  const { data, setData, put } = useForm<${model}Form>(item);

  function submit(e: React.FormEvent) {
    e.preventDefault();
    put(\`/$model/\${item.id}\`);
  }

  return (
    <form onSubmit={submit}>
$fieldsForm
      <button type="submit">Atualizar</button>
    </form>
  );
}
TSX;
    }
}
