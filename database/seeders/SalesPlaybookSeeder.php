<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Deal;
use App\Models\SalesPlaybook;
use Illuminate\Database\Seeder;

class SalesPlaybookSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $account = Account::first();

        if (!$account) {
            $this->command->error('No account found. Please run DatabaseSeeder first.');
            return;
        }

        $playbooks = [
            [
                'name' => 'Enterprise SaaS - Prospecting',
                'description' => 'Playbook for prospecting enterprise SaaS companies',
                'industries' => ['Technology', 'SaaS', 'Software'],
                'deal_stages' => [Deal::STAGE_PROSPECTING, Deal::STAGE_QUALIFICATION],
                'pain_points' => ['cost', 'scalability', 'integration'],
                'talking_points' => "1. Discuss how our solution reduces operational costs by up to 40%\n2. Highlight scalability features that support growth from 100 to 10,000+ users\n3. Emphasize seamless integration with existing tech stack\n4. Share case studies from similar enterprise SaaS companies\n5. Address security and compliance requirements",
                'email_template_subject' => 'How {{company_name}} can reduce costs and scale efficiently',
                'email_template_body' => "Hi {{customer_name}},\n\nI wanted to reach out regarding how our solution can help {{company_name}} address your key challenges:\n\n• Cost Reduction: Our platform can reduce your operational costs by up to 40%\n• Scalability: Seamlessly scale from 100 to 10,000+ users without performance issues\n• Integration: Easy integration with your existing tech stack\n\nI'd love to schedule a brief call to discuss how we've helped similar companies. Are you available for a 15-minute conversation this week?\n\nBest regards,\n{{date}}",
                'recommended_documents' => ['Enterprise Case Study - SaaS.pdf', 'Pricing Sheet - Enterprise.pdf', 'Integration Guide.pdf'],
                'objections_handling' => "Price Objection: 'Our solution pays for itself within 6 months through cost savings.'\n\nIntegration Concerns: 'We have pre-built integrations with 50+ popular tools, and our API makes custom integrations simple.'\n\nSecurity Questions: 'We're SOC 2 Type II certified and HIPAA compliant. Security is our top priority.'",
                'next_steps' => "1. Schedule discovery call\n2. Send case study and pricing\n3. Arrange product demo\n4. Connect with technical team for integration discussion",
                'tags' => ['enterprise', 'saas', 'prospecting'],
                'priority' => 90,
                'is_active' => true,
            ],
            [
                'name' => 'E-commerce - Proposal Stage',
                'description' => 'Playbook for e-commerce companies in proposal stage',
                'industries' => ['E-commerce', 'Retail', 'Online Retail'],
                'deal_stages' => [Deal::STAGE_PROPOSAL, Deal::STAGE_NEGOTIATION],
                'pain_points' => ['conversion', 'scalability', 'cost'],
                'talking_points' => "1. Our solution increases conversion rates by an average of 25%\n2. Handle traffic spikes during peak seasons without issues\n3. ROI typically achieved within 3-4 months\n4. Easy integration with Shopify, WooCommerce, and Magento\n5. 24/7 support during critical periods",
                'email_template_subject' => 'Proposal: Boost {{company_name}}\'s conversion rates',
                'email_template_body' => "Dear {{customer_name}},\n\nThank you for your interest in our solution for {{company_name}}. Based on our discussion, I've prepared a proposal that addresses your key needs:\n\n• Conversion Optimization: Increase conversion rates by 25% on average\n• Scalability: Handle traffic spikes during peak seasons\n• Quick ROI: Most customers see ROI within 3-4 months\n\nI've attached our detailed proposal and pricing. I'm happy to answer any questions or schedule a call to discuss.\n\nLooking forward to working with you!\n\nBest regards,\n{{date}}",
                'recommended_documents' => ['E-commerce Case Study.pdf', 'Proposal - {{deal_title}}.pdf', 'ROI Calculator.xlsx'],
                'objections_handling' => "Budget Concerns: 'We offer flexible payment plans and the ROI typically pays for itself in 3-4 months.'\n\nImplementation Time: 'Our average implementation time is 2-3 weeks with minimal disruption to your operations.'\n\nCompetitor Comparison: 'Our solution offers better integration, faster support response, and proven results in e-commerce.'",
                'next_steps' => "1. Review proposal together\n2. Address any questions or concerns\n3. Discuss implementation timeline\n4. Finalize contract terms",
                'tags' => ['e-commerce', 'proposal', 'retail'],
                'priority' => 85,
                'is_active' => true,
            ],
            [
                'name' => 'Healthcare - Compliance Focus',
                'description' => 'Playbook for healthcare organizations emphasizing compliance',
                'industries' => ['Healthcare', 'Health Tech', 'Medical'],
                'deal_stages' => [Deal::STAGE_QUALIFICATION, Deal::STAGE_PROPOSAL],
                'pain_points' => ['compliance', 'security', 'patient data'],
                'talking_points' => "1. HIPAA compliant solution with built-in security features\n2. SOC 2 Type II certified\n3. Patient data encryption at rest and in transit\n4. Audit trail for all data access\n5. Regular compliance audits and updates",
                'email_template_subject' => 'HIPAA-Compliant Solution for {{company_name}}',
                'email_template_body' => "Hello {{customer_name}},\n\nI understand that compliance and security are top priorities for {{company_name}}. Our solution is specifically designed for healthcare organizations:\n\n• HIPAA Compliant: Full compliance with HIPAA regulations\n• Security: SOC 2 Type II certified with end-to-end encryption\n• Patient Data Protection: Advanced security measures for patient data\n• Audit Trails: Complete audit trail for compliance reporting\n\nI'd be happy to schedule a call to discuss how we can help {{company_name}} meet your compliance requirements while improving efficiency.\n\nBest regards,\n{{date}}",
                'recommended_documents' => ['HIPAA Compliance Guide.pdf', 'Security Whitepaper.pdf', 'Healthcare Case Study.pdf'],
                'objections_handling' => "Compliance Questions: 'We're HIPAA compliant and undergo regular audits. We can provide all necessary documentation.'\n\nSecurity Concerns: 'We use end-to-end encryption and are SOC 2 Type II certified. Security is our foundation.'\n\nImplementation Complexity: 'We have a dedicated healthcare implementation team that understands your unique requirements.'",
                'next_steps' => "1. Schedule compliance review call\n2. Provide compliance documentation\n3. Arrange security assessment\n4. Discuss implementation with compliance team",
                'tags' => ['healthcare', 'compliance', 'security'],
                'priority' => 80,
                'is_active' => true,
            ],
            [
                'name' => 'Startup - Growth Focus',
                'description' => 'Playbook for startups focusing on growth and scalability',
                'industries' => ['Technology', 'SaaS', 'Startup'],
                'deal_stages' => [Deal::STAGE_PROSPECTING, Deal::STAGE_QUALIFICATION],
                'pain_points' => ['growth', 'scalability', 'cost'],
                'talking_points' => "1. Affordable pricing for startups with growth-friendly plans\n2. Scale from MVP to enterprise without switching platforms\n3. Quick setup - get started in days, not months\n4. Built for fast-growing companies\n5. Flexible pricing that grows with you",
                'email_template_subject' => 'Scale {{company_name}} with our startup-friendly solution',
                'email_template_body' => "Hi {{customer_name}},\n\nI noticed {{company_name}} is in the growth phase. Our solution is designed specifically for startups:\n\n• Startup-Friendly Pricing: Affordable plans that grow with you\n• Quick Setup: Get started in days, not months\n• Scalability: Grow from MVP to enterprise without switching\n• Growth Support: Features designed for fast-growing companies\n\nWould you be interested in a quick 15-minute call to see how we can help {{company_name}} scale efficiently?\n\nBest regards,\n{{date}}",
                'recommended_documents' => ['Startup Success Stories.pdf', 'Pricing - Startup Plans.pdf', 'Quick Start Guide.pdf'],
                'objections_handling' => "Budget Constraints: 'We offer startup-friendly pricing and flexible payment options. Many startups see ROI within the first month.'\n\nTime to Value: 'Our average setup time is 3-5 days. You can start seeing value immediately.'\n\nFuture Scalability: 'Our platform scales with you. Start small and grow without platform changes.'",
                'next_steps' => "1. Schedule quick demo\n2. Discuss startup pricing options\n3. Provide quick start guide\n4. Set up trial account",
                'tags' => ['startup', 'growth', 'scalability'],
                'priority' => 75,
                'is_active' => true,
            ],
            [
                'name' => 'General - Negotiation Stage',
                'description' => 'General playbook for negotiation stage deals',
                'industries' => null, // Applies to all industries
                'deal_stages' => [Deal::STAGE_NEGOTIATION, Deal::STAGE_CLOSING],
                'pain_points' => ['cost', 'value', 'implementation'],
                'talking_points' => "1. Highlight total value and ROI\n2. Discuss flexible payment terms\n3. Address implementation timeline concerns\n4. Provide references from similar customers\n5. Emphasize long-term partnership",
                'email_template_subject' => 'Finalizing {{deal_title}} - Next Steps',
                'email_template_body' => "Dear {{customer_name}},\n\nThank you for your interest in moving forward with {{deal_title}}. I wanted to summarize the key points:\n\n• Value Proposition: [Key benefits]\n• Implementation: [Timeline and process]\n• Support: [Ongoing support details]\n\nI'm happy to discuss any final questions or concerns. Let's schedule a call to finalize the details.\n\nBest regards,\n{{date}}",
                'recommended_documents' => ['Contract Template.pdf', 'Implementation Plan.pdf', 'Customer References.pdf'],
                'objections_handling' => "Price Negotiation: 'We can discuss flexible payment terms and volume discounts.'\n\nImplementation Concerns: 'We have a proven implementation process with dedicated support.'\n\nRisk Mitigation: 'We offer a satisfaction guarantee and can provide customer references.'",
                'next_steps' => "1. Finalize contract terms\n2. Schedule kickoff meeting\n3. Assign implementation team\n4. Set up account and access",
                'tags' => ['negotiation', 'closing', 'general'],
                'priority' => 70,
                'is_active' => true,
            ],
        ];

        foreach ($playbooks as $playbookData) {
            SalesPlaybook::create(array_merge($playbookData, ['account_id' => $account->id]));
        }

        $this->command->info('Created ' . count($playbooks) . ' sales playbooks.');
    }
}
