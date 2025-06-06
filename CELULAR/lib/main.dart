import 'package:flutter/material.dart';
import 'package:klubecash_app/features/auth/presentation/pages/login_page.dart';
// import 'package:klubecash_app/common/theme/app_theme.dart';

void main() {
  runApp(const MyApp());
}

class MyApp extends StatelessWidget {
  const MyApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'KlubeCash',
      // theme: AppTheme.lightTheme,
      debugShowCheckedModeBanner: false,
      home: LoginPage(),
    );
  }
}
