import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../providers/auth_provider.dart';
import '../theme.dart';
import 'customers_screen.dart';
import 'new_sale_screen.dart';

class HomeScreen extends StatelessWidget {
  const HomeScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();
    final userName = auth.currentUser?['name'] as String?;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Dashboard'),
        actions: [
          IconButton(
            icon: const Icon(Icons.logout_rounded),
            tooltip: 'Log out',
            onPressed: () => context.read<AuthProvider>().logout(),
          ),
          const SizedBox(width: 4),
        ],
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Welcome back${userName != null ? ', $userName' : ''}.',
              style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w700, color: AppColors.ink),
            ),
            const SizedBox(height: 4),
            const Text(
              'What would you like to do?',
              style: TextStyle(fontSize: 14, color: AppColors.inkSoft),
            ),
            const SizedBox(height: 20),
            GridView.count(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              crossAxisCount: 2,
              mainAxisSpacing: 14,
              crossAxisSpacing: 14,
              childAspectRatio: 1.05,
              children: [
                _DashboardTile(
                  icon: Icons.point_of_sale_rounded,
                  label: 'New Sale',
                  subtitle: 'Record a transaction',
                  onTap: () => Navigator.push(
                    context,
                    MaterialPageRoute(builder: (_) => const NewSaleScreen()),
                  ),
                ),
                _DashboardTile(
                  icon: Icons.people_alt_rounded,
                  label: 'Customers',
                  subtitle: 'Balances & payments',
                  onTap: () => Navigator.push(
                    context,
                    MaterialPageRoute(builder: (_) => const CustomersScreen()),
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

class _DashboardTile extends StatelessWidget {
  final IconData icon;
  final String label;
  final String subtitle;
  final VoidCallback onTap;

  const _DashboardTile({
    required this.icon,
    required this.label,
    required this.subtitle,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return Card(
      child: InkWell(
        borderRadius: BorderRadius.circular(14),
        onTap: onTap,
        child: Padding(
          padding: const EdgeInsets.all(18),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Container(
                width: 44,
                height: 44,
                alignment: Alignment.center,
                decoration: BoxDecoration(
                  color: AppColors.accentSoft,
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Icon(icon, size: 22, color: AppColors.accent),
              ),
              const Spacer(),
              Text(
                label,
                style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w600, color: AppColors.ink),
              ),
              const SizedBox(height: 2),
              Text(
                subtitle,
                style: const TextStyle(fontSize: 12.5, color: AppColors.inkSoft),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
