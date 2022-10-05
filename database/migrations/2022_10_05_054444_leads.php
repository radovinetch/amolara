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
        Schema::create('leads',
            function (Blueprint $blueprint): void {
                $blueprint->unsignedBigInteger('id')->primary();
                $blueprint->string('name');
                $blueprint->unsignedBigInteger('responsibleUserId')->nullable()->default(null);
                $blueprint->integer('groupId')->nullable()->default(null);
                $blueprint->unsignedBigInteger('createdBy')->nullable()->default(null);
                $blueprint->unsignedBigInteger('updatedBy')->nullable()->default(null);
                $blueprint->unsignedBigInteger('createdAt');
                $blueprint->unsignedBigInteger('updatedAt');
                $blueprint->unsignedBigInteger('accountId')->nullable()->default(null);
                $blueprint->unsignedBigInteger('pipelineId')->nullable()->default(null);
                $blueprint->unsignedBigInteger('statusId');
                $blueprint->unsignedBigInteger('closedAt')->nullable()->default(null);
                $blueprint->unsignedBigInteger('closestTaskAt')->nullable()->default(null);
                $blueprint->unsignedInteger('price');
                $blueprint->unsignedInteger('lossReasonId')->nullable()->default(null);
                $blueprint->string('lossReason')->nullable()->default(null);
                $blueprint->boolean('isDeleted')->default(false);
                //$blueprint->string('contacts')->nullable()->default(null);
                //$blueprint->string('company')->nullable()->default(null);
                $blueprint->unsignedBigInteger('companyId')->nullable()->default(null);

                $blueprint->boolean('isMerged')->nullable()->default(null);

                $blueprint->foreign('statusId')->references('id')->on('statuses')->onDelete('cascade')->onUpdate('cascade');
                $blueprint->foreign('responsibleUserId')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
                $blueprint->foreign('createdBy')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
                $blueprint->foreign('updatedBy')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
                $blueprint->foreign('companyId')->references('id')->on('companies')->onDelete('cascade')->onUpdate('cascade');
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
        Schema::dropIfExists('leads');
    }
};
