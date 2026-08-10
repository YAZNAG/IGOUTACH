import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/auth_provider.dart';
import '../../core/format.dart';
import '../../core/theme.dart';
import '../../core/widgets.dart';
import '../../models/expense.dart';
import 'create_expense_screen.dart';
import '../shared/period_export.dart';

/// Libellé et couleur d'un statut de charge.
(String, Color) expenseStatusBadge(String status) => switch (status) {
      'approved' => ('Approuvée', AppTheme.success),
      'rejected' => ('Refusée', AppTheme.danger),
      _ => ('En attente', AppTheme.warning),
    };

/// Charges : liste filtrable par statut, saisie, et validation/refus
/// par le responsable (permission `expense.approve`).
class ExpensesScreen extends StatefulWidget {
  const ExpensesScreen({super.key});

  @override
  State<ExpensesScreen> createState() => _ExpensesScreenState();
}

class _ExpensesScreenState extends State<ExpensesScreen> {
  final _api = ApiClient.instance;
  Periode _periode = Periode.moisEnCours();
  final _scrollController = ScrollController();

  final List<Expense> _expenses = [];
  int _page = 0;
  int _lastPage = 1;
  bool _loading = false;
  bool _firstLoadDone = false;
  String? _error;
  bool _offline = false;

  /// `null` = tous les statuts.
  String? _status;
  int? _decidingId;

  bool get _hasMore => _page < _lastPage;

  @override
  void initState() {
    super.initState();
    _scrollController.addListener(_onScroll);
    _load(reset: true);
  }

  @override
  void dispose() {
    _scrollController.dispose();
    super.dispose();
  }

  void _onScroll() {
    if (!_scrollController.hasClients) return;
    final position = _scrollController.position;
    if (position.pixels > position.maxScrollExtent - 300 &&
        _hasMore &&
        !_loading) {
      _load();
    }
  }

  Future<void> _load({bool reset = false}) async {
    if (_loading) return;
    setState(() {
      _loading = true;
      if (reset) {
        _error = null;
        _page = 0;
        _lastPage = 1;
        _expenses.clear();
        _firstLoadDone = false;
      }
    });

    try {
      final res = await _api.dio.get<Map<String, dynamic>>(
        '/expenses',
        queryParameters: {
          'page': _page + 1,
          'status': ?_status,
          'date_from': _periode.duIso,
          'date_to': _periode.auIso,
        },
      );
      final body = res.data!;
      final data = body['data'] as List<dynamic>? ?? [];
      final meta = body['meta'] as Map<String, dynamic>? ?? {};
      if (!mounted) return;
      setState(() {
        _page = (meta['current_page'] as num?)?.toInt() ?? _page + 1;
        _lastPage = (meta['last_page'] as num?)?.toInt() ?? _page;
        _expenses.addAll(
          data.map((e) => Expense.fromJson(e as Map<String, dynamic>)),
        );
        _firstLoadDone = true;
        _loading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _error = friendlyError(e);
        _offline = isNetworkError(e);
        _loading = false;
        _firstLoadDone = true;
      });
    }
  }

  void _changeStatus(String? status) {
    if (_status == status) return;
    setState(() => _status = status);
    _load(reset: true);
  }

  Future<void> _openCreate() async {
    final messenger = ScaffoldMessenger.of(context);
    final created = await Navigator.of(context).push<bool>(
      MaterialPageRoute(builder: (_) => const CreateExpenseScreen()),
    );
    if (created == true) {
      showSuccessSnack(
        messenger,
        'Charge enregistrée, en attente de validation.',
      );
      _load(reset: true);
    }
  }

  Future<void> _decide(Expense expense, String decision) async {
    final approving = decision == 'approved';
    final confirmed = await confirmAction(
      context,
      icon: approving ? Icons.check_circle_outline : Icons.cancel_outlined,
      title: approving ? 'Approuver la charge' : 'Refuser la charge',
      message: '${expense.label}\n${formatMoney(expense.amount)}\n\n'
          'Confirmer ${approving ? 'l\'approbation' : 'le refus'} ?',
      confirmLabel: approving ? 'Approuver' : 'Refuser',
      confirmColor: approving ? AppTheme.success : AppTheme.danger,
    );
    if (!confirmed || !mounted) return;

    final messenger = ScaffoldMessenger.of(context);
    setState(() => _decidingId = expense.id);
    try {
      await _api.dio.patch<Map<String, dynamic>>(
        '/expenses/${expense.id}/decide',
        data: {'decision': decision},
      );
      if (!mounted) return;
      setState(() => _decidingId = null);
      if (approving) {
        showSuccessSnack(messenger, 'Charge approuvée.');
      } else {
        showErrorSnack(messenger, 'Charge refusée.');
      }
      _load(reset: true);
    } catch (e) {
      if (!mounted) return;
      setState(() => _decidingId = null);
      showErrorSnack(messenger, friendlyError(e));
    }
  }

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();
    if (!auth.can('expense.create')) {
      return const NotAllowedView();
    }

    return Scaffold(
      appBar: AppBar(title: const Text('Charges')),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: _openCreate,
        icon: const Icon(Icons.add),
        label: const Text('Nouvelle charge'),
      ),
      body: Column(
        children: [
          // Liste et export sur la même période : le document reprend
          // exactement ce que l'écran affiche.
          PeriodBar(
            periode: _periode,
            journal: 'expenses',
            onChanged: (p) {
              setState(() => _periode = p);
              _load(reset: true);
            },
          ),
          _buildFilters(),
          Expanded(child: _buildBody(auth.can('expense.approve'))),
        ],
      ),
    );
  }

  Widget _buildFilters() {
    const options = <(String, String?)>[
      ('Toutes', null),
      ('En attente', 'pending'),
      ('Approuvées', 'approved'),
      ('Refusées', 'rejected'),
    ];

    return SingleChildScrollView(
      scrollDirection: Axis.horizontal,
      padding: const EdgeInsets.fromLTRB(12, 12, 12, 4),
      child: Row(
        children: options
            .map(
              (option) => Padding(
                padding: const EdgeInsets.only(right: 8),
                child: ChoiceChip(
                  label: Text(option.$1),
                  selected: _status == option.$2,
                  onSelected: (_) => _changeStatus(option.$2),
                ),
              ),
            )
            .toList(),
      ),
    );
  }

  Widget _buildBody(bool canApprove) {
    if (!_firstLoadDone) return const ListSkeleton(itemCount: 5, lines: 3);
    if (_error != null && _expenses.isEmpty) {
      return ErrorView(
        message: _error!,
        offline: _offline,
        onRetry: () => _load(reset: true),
      );
    }
    if (_expenses.isEmpty) {
      return EmptyView(
        icon: Icons.receipt_long_outlined,
        title: 'Aucune charge',
        message: _status == null
            ? 'Enregistrez ici les dépenses du lieu (carburant, '
                'électricité, réparations…).'
            : 'Aucune charge ne correspond à ce filtre.',
        actionLabel: _status == null ? 'Nouvelle charge' : null,
        onAction: _status == null ? _openCreate : null,
      );
    }

    return RefreshIndicator(
      onRefresh: () => _load(reset: true),
      child: ListView.builder(
        controller: _scrollController,
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.only(top: 4, bottom: 96),
        itemCount: _expenses.length + (_hasMore ? 1 : 0),
        itemBuilder: (context, index) {
          if (index >= _expenses.length) return const SkeletonCard(lines: 3);
          final expense = _expenses[index];
          return _ExpenseCard(
            expense: expense,
            deciding: _decidingId == expense.id,
            onApprove: canApprove && expense.isPending
                ? () => _decide(expense, 'approved')
                : null,
            onReject: canApprove && expense.isPending
                ? () => _decide(expense, 'rejected')
                : null,
          );
        },
      ),
    );
  }
}

class _ExpenseCard extends StatelessWidget {
  const _ExpenseCard({
    required this.expense,
    required this.deciding,
    this.onApprove,
    this.onReject,
  });

  final Expense expense;
  final bool deciding;
  final VoidCallback? onApprove;
  final VoidCallback? onReject;

  @override
  Widget build(BuildContext context) {
    final (statusLabel, statusColor) = expenseStatusBadge(expense.status);

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Expanded(
                  child: Text(
                    expense.label,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                      fontWeight: FontWeight.w600,
                      fontSize: 16,
                      height: 1.25,
                    ),
                  ),
                ),
                const SizedBox(width: 10),
                AmountText(formatMoney(expense.amount), fontSize: 17),
              ],
            ),
            const SizedBox(height: 6),
            Text(
              [
                if (expense.expenseDate != null) expense.expenseDate!,
                if ((expense.category ?? '').isNotEmpty) expense.category!,
                if ((expense.warehouse ?? '').isNotEmpty) expense.warehouse!,
                if ((expense.user ?? '').isNotEmpty) expense.user!,
              ].join(' · '),
              style: const TextStyle(
                fontSize: 14,
                color: AppTheme.textMuted,
                height: 1.3,
              ),
            ),
            const SizedBox(height: 10),
            Wrap(
              spacing: 6,
              runSpacing: 4,
              children: [
                StatusBadge(label: statusLabel, color: statusColor),
                if (expense.hasReceipt)
                  const StatusBadge(
                    label: 'Justificatif',
                    color: AppTheme.sky,
                  ),
              ],
            ),
            if (onApprove != null || onReject != null) ...[
              const SizedBox(height: 10),
              if (deciding)
                const Center(
                  child: SizedBox(
                    width: 20,
                    height: 20,
                    child: CircularProgressIndicator(strokeWidth: 2),
                  ),
                )
              else
                Row(
                  children: [
                    Expanded(
                      child: OutlinedButton.icon(
                        onPressed: onReject,
                        style: OutlinedButton.styleFrom(
                          foregroundColor: AppTheme.danger,
                          side: const BorderSide(color: AppTheme.danger),
                        ),
                        icon: const Icon(Icons.close, size: 18),
                        label: const Text('Refuser'),
                      ),
                    ),
                    const SizedBox(width: 8),
                    Expanded(
                      child: FilledButton.icon(
                        onPressed: onApprove,
                        style: FilledButton.styleFrom(
                          backgroundColor: AppTheme.success,
                          minimumSize: const Size(0, AppTheme.minTapTarget),
                        ),
                        icon: const Icon(Icons.check, size: 18),
                        label: const Text('Approuver'),
                      ),
                    ),
                  ],
                ),
            ],
          ],
        ),
      ),
    );
  }
}
