<?php

namespace App\Http\Controllers;

use App\Models\Balance;
use App\Models\Transaction;
use App\Models\User;
use App\OpenApi\Attributes\RequestFormData;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes\Get;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Parameter;
use OpenApi\Attributes\Post;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Response;
use OpenApi\Attributes\Schema;
use Throwable;

class BalanceController extends Controller
{
    /**
     * @throws Throwable
     */
    #[Post(
        path: '/deposit',
        operationId: 'depositUserBalance',
        description: 'Позволяет пополнить баланс пользователя на указанную сумму.',
        summary: 'Зачисление средств пользователю',
        tags: ['Balance']
    )]
    #[RequestFormData(
        requiredProps: ['user_id', 'amount'],
        properties: [
            new Property(property: 'user_id', type: 'integer', example: 1),
            new Property(property: 'amount', type: 'number', example: 500.00),
            new Property(property: 'comment', type: 'string', example: 'Пополнение через карту'),
        ]
    )]
    #[Response(
        response: 200,
        description: 'Баланс успешно пополнен',
        content: new JsonContent(
            properties: [
                new Property(property: 'message', type: 'string', example: 'Deposited successfully')
            ]
        )
    )]
    #[Response(
        response: 422,
        description: 'Ошибка валидации',
    )]
    public function deposit(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:0.01',
            'comment' => 'nullable|string'
        ]);

        DB::transaction(function () use ($data) {
            $balance = Balance::firstOrCreate(['user_id' => $data['user_id']]);

            $balance->increment('amount', $data['amount']);

            Transaction::create([
                'user_id' => $data['user_id'],
                'amount' => $data['amount'],
                'type' => 'deposit',
                'comment' => $data['comment'] ?? null
            ]);
        });

        return response()->json(['message' => 'Deposited successfully']);
    }

    /**
     * @throws Throwable
     */
    #[Post(
        path: '/withdraw',
        operationId: 'withdrawUserBalance',
        description: 'Позволяет списать средства с баланса пользователя.',
        summary: 'Списание средств',
        tags: ['Balance']
    )]
    #[RequestFormData(
        requiredProps: ['user_id', 'amount'],
        properties: [
            new Property(property: 'user_id', type: 'integer', example: 1),
            new Property(property: 'amount', type: 'number', example: 200.00),
            new Property(property: 'comment', type: 'string', example: 'Покупка подписки'),
        ]
    )]
    #[Response(
        response: 200,
        description: 'Успешное списание',
        content: new JsonContent(
            properties: [
                new Property(property: 'message', type: 'string', example: 'Withdrawn successfully')
            ]
        )
    )]
    #[Response(
        response: 409,
        description: 'Недостаточно средств',
        content: new JsonContent(
            properties: [
                new Property(property: 'error', type: 'string', example: 'Insufficient funds')
            ]
        )
    )]
    #[Response(response: 422, description: 'Ошибка валидации')]
    public function withdraw(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:0.01',
            'comment' => 'nullable|string'
        ]);

        return DB::transaction(function () use ($data) {
            $balance = Balance::where('user_id', $data['user_id'])->lockForUpdate()->first();

            if (!$balance || $balance->amount < $data['amount']) {
                return response()->json(['error' => 'Insufficient funds'], 409);
            }

            $balance->decrement('amount', $data['amount']);

            Transaction::create([
                'user_id' => $data['user_id'],
                'amount' => $data['amount'],
                'type' => 'withdraw',
                'comment' => $data['comment'] ?? null
            ]);

            return response()->json(['message' => 'Withdrawn successfully']);
        });
    }

    /**
     * @throws Throwable
     */
    #[Post(
        path: '/transfer',
        operationId: 'transferBalanceBetweenUsers',
        description: 'Перевод денег с одного пользователя на другого.',
        summary: 'Перевод средств между пользователями',
        tags: ['Balance']
    )]
    #[RequestFormData(
        requiredProps: ['from_user_id', 'to_user_id', 'amount'],
        properties: [
            new Property(property: 'from_user_id', type: 'integer', example: 1),
            new Property(property: 'to_user_id', type: 'integer', example: 2),
            new Property(property: 'amount', type: 'number', example: 150.00),
            new Property(property: 'comment', type: 'string', example: 'Перевод другу'),
        ]
    )]
    #[Response(
        response: 200,
        description: 'Успешный перевод',
        content: new JsonContent(
            properties: [
                new Property(property: 'message', type: 'string', example: 'Transfer completed')
            ]
        )
    )]
    #[Response(
        response: 409,
        description: 'Недостаточно средств',
        content: new JsonContent(
            properties: [
                new Property(property: 'error', type: 'string', example: 'Insufficient funds')
            ]
        )
    )]
    #[Response(response: 422, description: 'Ошибка валидации')]
    public function transfer(Request $request)
    {
        $data = $request->validate([
            'from_user_id' => 'required|exists:users,id',
            'to_user_id' => 'required|exists:users,id|different:from_user_id',
            'amount' => 'required|numeric|min:0.01',
            'comment' => 'nullable|string'
        ]);

        DB::transaction(function () use ($data) {

            $from = Balance::where('user_id', $data['from_user_id'])->lockForUpdate()->first();
            $to   = Balance::firstOrCreate(['user_id' => $data['to_user_id']]);

            if (!$from || $from->amount < $data['amount']) {
                throw new HttpResponseException(
                    response()->json(['error' => 'Insufficient funds'], 409)
                );
            }

            $from->decrement('amount', $data['amount']);
            $to->increment('amount', $data['amount']);

            Transaction::create([
                'user_id' => $data['from_user_id'],
                'amount' => $data['amount'],
                'type' => 'transfer_out',
                'comment' => $data['comment'] ?? null
            ]);

            Transaction::create([
                'user_id' => $data['to_user_id'],
                'amount' => $data['amount'],
                'type' => 'transfer_in',
                'comment' => $data['comment'] ?? null
            ]);
        });

        return response()->json(['message' => 'Transfer completed']);
    }

    #[Get(
        path: '/balance/{user_id}',
        operationId: 'getUserBalance',
        description: 'Возвращает баланс по ID пользователя.',
        summary: 'Получение текущего баланса пользователя',
        tags: ['Balance']
    )]
    #[Parameter(
        name: 'user_id',
        description: 'ID пользователя',
        in: 'path',
        required: true,
        schema: new Schema(type: 'integer')
    )]
    #[Response(
        response: 200,
        description: 'Текущий баланс',
        content: new JsonContent(
            properties: [
                new Property(property: 'user_id', type: 'integer', example: 1),
                new Property(property: 'balance', type: 'number', example: 350.00),
            ]
        )
    )]
    #[Response(response: 404, description: 'Пользователь не найден')]
    public function balance(User $user)
    {
        $balance = Balance::firstOrCreate(['user_id' => $user->id]);

        return response()->json([
            'user_id' => $user->id,
            'balance' => $balance->amount
        ]);
    }
}
