import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/auth_provider.dart';
import '../../core/format.dart';
import '../../core/theme.dart';
import '../../core/widgets.dart';
import '../../models/expense.dart';
import '../shared/warehouse_scope.dart';

/// Saisie d'une charge (POST /expenses).
///
/// Le backend n'a qu'un seul champ texte (`label`) : il sert de description.
/// Le « lieu » est le `warehouse_id` (facultatif côté serveur).
class CreateExpenseScreen extends StatefulWidget {
  const CreateExpenseScreen({super.key});

  @override
  State<CreateExpenseScreen> createState() => _CreateExpenseScreenState();
}

class _CreateExpenseScreenState extends State<CreateExpenseScreen> {
  final _api = ApiClient.instance;
  final _formKey = GlobalKey<FormState>();

  final _label = TextEditingController();
  final _amount = TextEditingController();

  List<ExpenseCategory> _categories = [];
  int? _categoryId;

  WarehouseScope? _scope;

  DateTime _date = DateTime.now();
  bool _loading = true;
  String? _loadError;
  bool _saving = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    _label.dispose();
    _amount.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _loadError = null;
    });
    final userWarehouseId = context.read<AuthProvider>().user?.warehouseId;
    try {
      final res =
          await _api.dio.get<Map<String, dynamic>>('/expense-categories');
      final data = res.data!['data'] as List<dynamic>? ?? [];
      final categories = data
          .map((e) => ExpenseCategory.fromJson(e as Map<String, dynamic>))
          .toList();
      final scope = await WarehouseScope.load(userWarehouseId);
      if (!mounted) return;
      setState(() {
        _categories = categories;
        _categoryId = categories.isEmpty ? null : categories.first.id;
        _scope = scope;
        _loading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _loadError = friendlyError(e);
        _loading = false;
      });
    }
  }

  Future<void> _pickDate() async {
    final picked = await showDatePicker(
      context: context,
      initialDate: _date,
      firstDate: DateTime(2020),
      lastDate: DateTime.now().add(const Duration(days: 1)),
      helpText: 'Date de la charge',
    );
    if (picked != null) setState(() => _date = picked);
  }

  Future<void> _submit() async {
    if (!(_formKey.currentState?.validate() ?? false)) return;
    if (_categoryId == null) {
      setState(() => _error = 'Sélectionnez une catégorie.');
      return;
    }

    setState(() {
      _saving = true;
      _error = null;
    });

    try {
      await _api.dio.post<Map<String, dynamic>>(
        '/expenses',
        data: {
          'expense_category_id': _categoryId,
          'warehouse_id': ?_scope?.selectedId,
          'label': _label.text.trim(),
          'amount': double.parse(_amount.text.trim().replaceAll(',', '.')),
          'expense_date': apiDate(_date),
        },
      );
      if (!mounted) return;
      Navigator.of(context).pop(true);
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _saving = false;
        _error = friendlyError(e);
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    if (!context.watch<AuthProvider>().can('expense.create')) {
      return const NotAllowedView();
    }

    return Scaffold(
      appBar: AppBar(title: const Text('Nouvelle charge')),
      body: _loading
          ? const LoadingView()
          : _loadError != null
              ? ErrorView(message: _loadError!, onRetry: _load)
              : _buildForm(),
    );
  }

  Widget _buildForm() {
    final scope = _scope;

    return Form(
      key: _formKey,
      child: ListView(
        padding: const EdgeInsets.fromLTRB(16, 16, 16, 32),
        children: [
          if (_categories.isEmpty)
            const Padding(
              padding: EdgeInsets.only(bottom: 12),
              child: Text(
                'Aucune catégorie de charge n\'est définie. '
                'Demandez à un responsable d\'en créer une.',
                style: TextStyle(color: AppTheme.danger, fontSize: 13),
              ),
            )
          else
            DropdownButtonFormField<int>(
              initialValue: _categoryId,
              isExpanded: true,
              decoration: const InputDecoration(
                labelText: 'Catégorie *',
                prefixIcon: Icon(Icons.category_outlined),
              ),
              items: _categories
                  .map(
                    (c) => DropdownMenuItem(
                      value: c.id,
                      child: Text(c.name, overflow: TextOverflow.ellipsis),
                    ),
                  )
                  .toList(),
              onChanged: (value) => setState(() => _categoryId = value),
            ),
          const SizedBox(height: 12),
          TextFormField(
            controller: _amount,
            keyboardType: const TextInputType.numberWithOptions(decimal: true),
            inputFormatters: [
              FilteringTextInputFormatter.allow(RegExp(r'[0-9.,]')),
            ],
            decoration: const InputDecoration(
              labelText: 'Montant (DH) *',
              prefixIcon: Icon(Icons.payments_outlined),
            ),
            validator: (value) {
              final raw = (value ?? '').trim().replaceAll(',', '.');
              final parsed = double.tryParse(raw);
              if (parsed == null || parsed <= 0) {
                return 'Saisissez un montant supérieur à 0.';
              }
              return null;
            },
          ),
          const SizedBox(height: 12),
          InkWell(
            onTap: _pickDate,
            borderRadius: BorderRadius.circular(12),
            child: InputDecorator(
              decoration: const InputDecoration(
                labelText: 'Date *',
                prefixIcon: Icon(Icons.event_outlined),
              ),
              child: Text(formatDate(_date)),
            ),
          ),
          const SizedBox(height: 12),
          TextFormField(
            controller: _label,
            maxLength: 191,
            maxLines: 2,
            decoration: const InputDecoration(
              labelText: 'Description *',
              prefixIcon: Icon(Icons.notes_outlined),
              counterText: '',
            ),
            validator: (value) => (value ?? '').trim().isEmpty
                ? 'La description est obligatoire.'
                : null,
          ),
          const SizedBox(height: 12),
          if (scope != null && scope.canChoose && scope.warehouses.isNotEmpty)
            DropdownButtonFormField<int>(
              initialValue: scope.selectedId,
              isExpanded: true,
              decoration: const InputDecoration(
                labelText: 'Lieu',
                prefixIcon: Icon(Icons.warehouse_outlined),
              ),
              items: scope.warehouses
                  .map(
                    (w) => DropdownMenuItem(
                      value: w.id,
                      child: Text(w.label, overflow: TextOverflow.ellipsis),
                    ),
                  )
                  .toList(),
              onChanged: (value) => setState(
                () => _scope = scope.copyWith(selectedId: value),
              ),
            )
          else if (scope?.selectedLabel != null)
            InfoBanner(
              icon: Icons.warehouse_outlined,
              message: 'Lieu : ${scope!.selectedLabel}',
            ),
          if (_error != null) ...[
            const SizedBox(height: 16),
            Text(
              _error!,
              style: const TextStyle(color: AppTheme.danger, fontSize: 13),
            ),
          ],
          const SizedBox(height: 24),
          FilledButton.icon(
            onPressed: _saving || _categories.isEmpty ? null : _submit,
            icon: _saving
                ? const SizedBox(
                    width: 20,
                    height: 20,
                    child: CircularProgressIndicator(
                      strokeWidth: 2,
                      color: Colors.white,
                    ),
                  )
                : const Icon(Icons.check),
            label: const Text('Enregistrer la charge'),
          ),
        ],
      ),
    );
  }
}
