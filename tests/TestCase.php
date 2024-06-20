<?php

namespace Tests;

use App\Models\User;
use Closure;
use Database\Seeders\PermissionRoleSeeder;
use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\SQLiteBuilder;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Fluent;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected $admin;

    protected $manager;

    protected $employee;

    protected $user;

    public function __construct(?string $name = null, array $data = [], string $dataName = '')
    {
        $this->resolveSqlite();
        parent::__construct($name, $data, $dataName);
    }

    public function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionRoleSeeder::class);

        $this->admin = User::factory()->create(['two_factor_enabled' => true])->assignRole('Admin');
        $this->manager = User::factory()->create()->assignRole('Manager');
        $this->employee = User::factory()->create()->assignRole('Employee');
        $this->user = User::factory()->create()->assignRole('User');
    }

    public function resolveSqlite()
    {
        Connection::resolverFor('sqlite', function ($connection, $database, $prefix, $config) {
            return new class($connection, $database, $prefix, $config) extends SQLiteConnection {
                public function getSchemaBuilder()
                {
                    if ($this->schemaGrammar === null) {
                        $this->useDefaultSchemaGrammar();
                    }

                    return new class($this) extends SQLiteBuilder {
                        protected function createBlueprint($table, Closure $callback = null)
                        {
                            return new class($table, $callback) extends Blueprint {
                                public function dropForeign($index)
                                {
                                    return new Fluent();
                                }
                            };
                        }
                    };
                }
            };
        });
    }
}
