<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentP2PsTable extends Migration
{
    public function up()
    {
        Schema::create('payment_p2_ps', function (Blueprint $table) {
            $table->bigIncrements('id');

            //ACQ section
            $table->uuid('uuid')->unique()->index()->comment('UUID платежа внутренний');
            $table->string('paygate_id')->unique()->index()->comment('UUID платежа в платежном шлюзе');
            $table->string('pay_url')->nullable()->unique()->comment('URL платежной страницы экв.');
            $table->unsignedBigInteger('amount')->comment('Сумма платежа');
            $table->string('status', 16);
            $table->string('paygate_status', 16)->nullable();
            $table->string('paygate_description')->nullable();
            $table->string('card', 16)->nullable()->index()->comment('Карта отправителя');
            $table->string('rrn', 32)->nullable()->index()->comment('RRN');
            $table->string('approval', 32)->nullable()->index()->comment('Approval Code');

            //A2C section
            $table->uuid('a2c_uuid')->unique()->index()->comment('UUID платежа внутренний');
            $table->string('a2c_status', 16)->nullable()->comment('текущий общий A2C');
            $table->string('a2c_paygate_order_status', 16)->nullable()->comment('статус A2C в шлюзе');
            $table->string('a2c_paygate_payment_status', 16)->nullable()->comment('статус A2C в шлюзе');
            $table->string('a2c_paygate_description')->nullable();
            $table->string('a2c_paygate_id')->nullable()->unique()->index()->comment('ID платежа в платежном шлюзе');
            $table->unsignedBigInteger('a2c_amount')->comment('Сумма зачисления');
            $table->unsignedBigInteger('a2c_fee')->comment('Сумма комиссии, которую удержали');
            $table->string('a2c_card', 16)->nullable()->index()->comment('Маска карты получателя');
            $table->string('a2c_rrn', 32)->nullable()->index()->comment('RRN');
            $table->string('a2c_approval', 32)->nullable()->index()->comment('Approval Code');
            $table->string('a2c_comment')->nullable()->comment('комментарий');

            $table->string('phone', 15)->nullable()->comment('Телефон отправителя');
            $table->ipAddress('ip')->comment('IP адрес пользователя');

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('payment_p2_ps');
    }
}
