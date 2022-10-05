<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tokens',
            function (Blueprint $blueprint): void {
                $blueprint->string('token_type');
                $blueprint->string('expires_in');
                $blueprint->string('access_token', 4096);
                $blueprint->string('refresh_token', 4096);
            }
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tokens');
    }
};
