<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ContractQuotationSeeder extends Seeder
{
    public function run(): void
    {
        $account = Account::first();
        $user = User::where('account_id', $account->id)->first();
        $customers = Customer::where('account_id', $account->id)->get();
        $products = Product::where('account_id', $account->id)->get();

        if ($customers->isEmpty() || $products->isEmpty()) {
            $this->command->warn('Need customers & products. Run CustomerSuccessSeeder & ProductSeeder first.');
            return;
        }

        // Create Quotations
        $quotations = [];
        $quoteStatuses = ['draft', 'sent', 'accepted', 'accepted', 'rejected', 'expired'];

        for ($i = 1; $i <= 15; $i++) {
            $status = $quoteStatuses[array_rand($quoteStatuses)];
            $issueDate = Carbon::now()->subDays(rand(5, 60));
            $customer = $customers->random();

            $quotation = Quotation::create([
                'account_id' => $account->id,
                'quote_number' => 'QT-2026-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'title' => 'Báo giá cho ' . $customer->name,
                'status' => $status,
                'customer_id' => $customer->id,
                'subtotal' => 0,
                'discount_amount' => 0,
                'discount_percent' => rand(0, 15),
                'tax_amount' => 0,
                'total' => 0,
                'currency' => 'VND',
                'issue_date' => $issueDate,
                'valid_until' => $issueDate->copy()->addDays(30),
                'notes' => 'Báo giá dịch vụ cho ' . $customer->name,
                'terms' => 'Thanh toán 50% trước khi bắt đầu, 50% khi hoàn thành. Bảo hành 6 tháng.',
                'created_by' => $user->id,
                'approved_by' => $status === 'accepted' ? $user->id : null,
                'approved_at' => $status === 'accepted' ? $issueDate->copy()->addDays(rand(1, 5)) : null,
                'sent_at' => in_array($status, ['sent', 'accepted', 'rejected']) ? $issueDate->copy()->addDay() : null,
            ]);

            // Add 2-4 items
            $subtotal = 0;
            $itemCount = rand(2, 4);
            for ($j = 0; $j < $itemCount; $j++) {
                $product = $products->random();
                $qty = rand(1, 5);
                $price = $product->unit_price;
                $discount = rand(0, 10);
                $lineTotal = $qty * $price * (1 - $discount / 100);

                QuotationItem::create([
                    'quotation_id' => $quotation->id,
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'description' => $product->description,
                    'unit' => $product->unit,
                    'quantity' => $qty,
                    'unit_price' => $price,
                    'discount_percent' => $discount,
                    'tax_rate' => 10,
                    'total' => $lineTotal,
                    'sort_order' => $j + 1,
                ]);

                $subtotal += $lineTotal;
            }

            $discountAmount = $subtotal * $quotation->discount_percent / 100;
            $taxAmount = ($subtotal - $discountAmount) * 0.1;
            $total = $subtotal - $discountAmount + $taxAmount;

            $quotation->update([
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'tax_amount' => $taxAmount,
                'total' => $total,
            ]);

            $quotations[] = $quotation;
        }

        // Create Contracts from accepted quotations
        $acceptedQuotes = collect($quotations)->filter(fn ($q) => $q->status === 'accepted');
        $contractStatuses = ['draft', 'active', 'active', 'active', 'completed', 'expired'];
        $contractCount = 0;

        foreach ($acceptedQuotes as $i => $quote) {
            $startDate = Carbon::now()->subMonths(rand(0, 6));
            $status = $contractStatuses[array_rand($contractStatuses)];

            Contract::create([
                'account_id' => $account->id,
                'contract_number' => 'CT-2026-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                'title' => 'Hợp đồng - ' . $quote->title,
                'status' => $status,
                'contract_type' => collect(['one_time', 'recurring', 'retainer', 'project_based'])->random(),
                'customer_id' => $quote->customer_id,
                'quotation_id' => $quote->id,
                'value' => $quote->total,
                'currency' => 'VND',
                'payment_terms' => '50% trước, 50% sau khi hoàn thành',
                'scope_of_work' => 'Theo phạm vi công việc trong báo giá ' . $quote->quote_number,
                'terms_conditions' => 'Điều khoản hợp đồng chuẩn. Bảo hành 6 tháng sau bàn giao. Hỗ trợ kỹ thuật trong giờ hành chính.',
                'start_date' => $startDate,
                'end_date' => $startDate->copy()->addMonths(rand(6, 24)),
                'signed_date' => $startDate->copy()->subDays(rand(1, 7)),
                'auto_renew' => rand(0, 1),
                'created_by' => $user->id,
                'approved_by' => $user->id,
                'approved_at' => $startDate->copy()->subDays(rand(1, 3)),
                'signed_by_client' => fake()->name(),
                'signed_by_company' => 'Admin BED',
            ]);
            $contractCount++;
        }

        $this->command->info('Created 15 quotations & ' . $contractCount . ' contracts.');
    }
}
