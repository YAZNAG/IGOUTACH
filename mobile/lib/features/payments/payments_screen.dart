import 'dart:async';

import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/auth_provider.dart';
import '../../core/format.dart';
import '../../core/theme.dart';
import '../../core/widgets.dart';
import '../../models/aging_row.dart';
import '../../models/customer.dart';
import '../../models/payment.dart';
import '../shared/payment_sheet.dart';

/// Règlements clients : encaissements (GET /payments) et balance âgée
/// (GET /customers-aging), avec le cycle de vie des chèques.
class PaymentsScreen extends StatefulWidget {
  const PaymentsScreen({super.key});

  @override
  State<PaymentsScreen> createState() => _PaymentsScreenState();
}

class _PaymentsScreenState extends State<PaymentsScreen> {
  /// Incrémenté après un encaissement : force le rechargement des onglets.
  int _reloadToken = 0;

  Future<void> _openPaymentSheet() async {
    final messenger = ScaffoldMessenger.of(context);
    final customer = await showModalBottomSheet<Customer>(
      context: context,
      isScrollControlled: true,
      builder: (_) => const _CustomerPickerSheet(),
    );
    if (customer == null || !mounted) return;

    final saved = await showPaymentSheet(
      context,
      customerId: customer.id,
      customerName: customer.name,
      dueAmount: customer.balance > 0 ? customer.balance : null,
    );
    if (!mounted || !saved) return;

    messenger.showSnackBar(const SnackBar(
      content: Text('Règlement enregistré.'),
      backgroundColor: AppTheme.success,
    ));
    setState(() => _reloadToken++);
  }

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();
    if (!auth.can('payment.view')) {
      return const NotAllowedView();
    }

    return DefaultTabController(
      length: 2,
      child: Scaffold(
        appBar: AppBar(
          title: const Text('Règlements'),
          bottom: const TabBar(
            labelColor: Colors.white,
            unselectedLabelColor: Colors.white70,
            indicatorColor: AppTheme.sky,
            tabs: [
              Tab(text: 'Encaissements'),
              Tab(text: 'Balance âgée'),
            ],
          ),
        ),
        floatingActionButton: auth.can('payment.create')
            ? FloatingActionButton.extended(
                onPressed: _openPaymentSheet,
                icon: const Icon(Icons.add),
                label: const Text('Encaisser'),
              )
            : null,
        body: TabBarView(
          children: [
            _PaymentsTab(key: ValueKey('payments-$_reloadToken')),
            _AgingTab(key: ValueKey('aging-$_reloadToken')),
          ],
        ),
      ),
    );
  }
}

// ── Onglet « Encaissements » ────────────────────────────────────────────────

class _PaymentsTab extends StatefulWidget {
  const _PaymentsTab({super.key});

  @override
  State<_PaymentsTab> createState() => _PaymentsTabState();
}

class _PaymentsTabState extends State<_PaymentsTab> {
  final _api = ApiClient.instance;
  final _scrollController = ScrollController();

  final List<PaymentRow> _rows = [];
  int _page = 0;
  int _lastPage = 1;
  bool _loading = false;
  bool _firstLoadDone = false;
  String? _error;
  int? _busyId;

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
        _rows.clear();
        _firstLoadDone = false;
      }
    });

    try {
      // PaymentController::index ne borne pas la taille de page (20 fixes)
      // et n'accepte que le filtre `customer_id`.
      final res = await _api.dio.get<Map<String, dynamic>>(
        '/payments',
        queryParameters: {'page': _page + 1},
      );
      final body = res.data!;
      final data = body['data'] as List<dynamic>? ?? [];
      final meta = body['meta'] as Map<String, dynamic>? ?? {};
      if (!mounted) return;
      setState(() {
        _page = (meta['current_page'] as num?)?.toInt() ?? _page + 1;
        _lastPage = (meta['last_page'] as num?)?.toInt() ?? _page;
        _rows.addAll(
          data.map((e) => PaymentRow.fromJson(e as Map<String, dynamic>)),
        );
        _firstLoadDone = true;
        _loading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _error = friendlyError(e);
        _loading = false;
        _firstLoadDone = true;
      });
    }
  }

  /// Fait avancer le cycle du chèque (PATCH /payments/{id}/cheque).
  Future<void> _setChequeStatus(PaymentRow row, String status) async {
    final messenger = ScaffoldMessenger.of(context);
    setState(() => _busyId = row.id);
    try {
      await _api.dio.patch<Map<String, dynamic>>(
        '/payments/${row.id}/cheque',
        data: {'status': status},
      );
      if (!mounted) return;
      setState(() => _busyId = null);
      messenger.showSnackBar(SnackBar(
        content: Text('Chèque ${row.reference} : ${_chequeLabel(status)}.'),
        backgroundColor: AppTheme.success,
      ));
      _load(reset: true);
    } catch (e) {
      if (!mounted) return;
      setState(() => _busyId = null);
      messenger.showSnackBar(SnackBar(
        content: Text(friendlyError(e)),
        backgroundColor: AppTheme.danger,
      ));
    }
  }

  @override
  Widget build(BuildContext context) {
    final canManageCheque = context.watch<AuthProvider>().can('payment.create');

    if (!_firstLoadDone) return const LoadingView();
    if (_error != null && _rows.isEmpty) {
      return ErrorView(message: _error!, onRetry: () => _load(reset: true));
    }
    if (_rows.isEmpty) {
      return const EmptyView(
        icon: Icons.payments_outlined,
        message: 'Aucun encaissement enregistré.',
      );
    }

    return RefreshIndicator(
      onRefresh: () => _load(reset: true),
      child: ListView.builder(
        controller: _scrollController,
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.only(top: 8, bottom: 88),
        itemCount: _rows.length + (_hasMore ? 1 : 0),
        itemBuilder: (context, index) {
          if (index >= _rows.length) {
            return const Padding(
              padding: EdgeInsets.all(16),
              child: Center(child: CircularProgressIndicator()),
            );
          }
          final row = _rows[index];
          return _PaymentTile(
            row: row,
            busy: _busyId == row.id,
            onChequeStatus: canManageCheque && row.isCheque
                ? (status) => _setChequeStatus(row, status)
                : null,
          );
        },
      ),
    );
  }
}

/// Libellé français d'un statut de chèque.
String _chequeLabel(String? status) => switch (status) {
      'received' => 'Reçu',
      'deposited' => 'Déposé',
      'cleared' => 'Encaissé',
      'bounced' => 'Impayé',
      _ => '—',
    };

Color _chequeColor(String? status) => switch (status) {
      'deposited' => AppTheme.sky,
      'cleared' => AppTheme.success,
      'bounced' => AppTheme.danger,
      _ => AppTheme.warning,
    };

class _PaymentTile extends StatelessWidget {
  const _PaymentTile({
    required this.row,
    required this.busy,
    this.onChequeStatus,
  });

  final PaymentRow row;
  final bool busy;

  /// `null` sans la permission `payment.create` ou hors règlement par chèque.
  final ValueChanged<String>? onChequeStatus;

  /// Étapes proposées selon l'avancement du chèque.
  List<String> get _nextStatuses => switch (row.chequeStatus) {
        'received' => const ['deposited', 'cleared', 'bounced'],
        'deposited' => const ['cleared', 'bounced'],
        _ => const [],
      };

  @override
  Widget build(BuildContext context) {
    final steps = onChequeStatus == null ? const <String>[] : _nextStatuses;

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        row.reference,
                        style: const TextStyle(
                          fontFamily: 'monospace',
                          fontWeight: FontWeight.bold,
                          color: AppTheme.navy,
                        ),
                      ),
                      const SizedBox(height: 2),
                      Text(
                        row.customer ?? 'Client inconnu',
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(fontSize: 13),
                      ),
                      Text(
                        [
                          row.receivedAt ?? '—',
                          if ((row.method ?? '').isNotEmpty) row.method!,
                        ].join(' · '),
                        style: TextStyle(
                          fontSize: 11,
                          color: Colors.grey.shade600,
                        ),
                      ),
                    ],
                  ),
                ),
                Text(
                  formatMoney(row.amount),
                  style: const TextStyle(
                    fontWeight: FontWeight.bold,
                    fontSize: 15,
                    color: AppTheme.success,
                  ),
                ),
              ],
            ),
            if (row.isCheque) ...[
              const SizedBox(height: 8),
              Wrap(
                spacing: 6,
                runSpacing: 4,
                crossAxisAlignment: WrapCrossAlignment.center,
                children: [
                  StatusBadge(
                    label: 'Chèque : ${_chequeLabel(row.chequeStatus)}',
                    color: _chequeColor(row.chequeStatus),
                  ),
                  if ((row.chequeReference ?? '').isNotEmpty)
                    Text(
                      'N° ${row.chequeReference}',
                      style: TextStyle(
                        fontSize: 11,
                        color: Colors.grey.shade600,
                      ),
                    ),
                ],
              ),
              if (steps.isNotEmpty) ...[
                const SizedBox(height: 8),
                busy
                    ? const Padding(
                        padding: EdgeInsets.symmetric(vertical: 8),
                        child: SizedBox(
                          width: 20,
                          height: 20,
                          child: CircularProgressIndicator(strokeWidth: 2),
                        ),
                      )
                    : Wrap(
                        spacing: 8,
                        runSpacing: 4,
                        children: steps
                            .map(
                              (status) => OutlinedButton(
                                onPressed: () => onChequeStatus!(status),
                                style: OutlinedButton.styleFrom(
                                  foregroundColor: _chequeColor(status),
                                  side: BorderSide(
                                    color: _chequeColor(status),
                                  ),
                                  visualDensity: VisualDensity.compact,
                                ),
                                child: Text(_chequeLabel(status)),
                              ),
                            )
                            .toList(),
                      ),
              ],
            ],
          ],
        ),
      ),
    );
  }
}

// ── Onglet « Balance âgée » ─────────────────────────────────────────────────

class _AgingTab extends StatefulWidget {
  const _AgingTab({super.key});

  @override
  State<_AgingTab> createState() => _AgingTabState();
}

class _AgingTabState extends State<_AgingTab> {
  final _api = ApiClient.instance;

  List<AgingRow> _rows = [];
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final res = await _api.dio.get<Map<String, dynamic>>('/customers-aging');
      final data = res.data!['data'] as List<dynamic>? ?? [];
      final rows = data
          .map((e) => AgingRow.fromJson(e as Map<String, dynamic>))
          .toList()
        ..sort((a, b) => b.totalDue.compareTo(a.totalDue));
      if (!mounted) return;
      setState(() {
        _rows = rows;
        _loading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _error = friendlyError(e);
        _loading = false;
      });
    }
  }

  double _sum(double Function(AgingRow) selector) =>
      _rows.fold(0, (total, row) => total + selector(row));

  @override
  Widget build(BuildContext context) {
    if (_loading) return const LoadingView();
    if (_error != null) return ErrorView(message: _error!, onRetry: _load);

    return RefreshIndicator(
      onRefresh: _load,
      child: ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.only(bottom: 88),
        children: [
          _buildTotals(),
          if (_rows.isEmpty)
            const Padding(
              padding: EdgeInsets.only(top: 48),
              child: EmptyView(
                icon: Icons.credit_score,
                message: 'Aucun encours client.',
              ),
            )
          else
            ..._rows.map((row) => _AgingCard(row: row)),
        ],
      ),
    );
  }

  Widget _buildTotals() {
    return Card(
      color: AppTheme.navy,
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'Total dû par les clients',
              style: TextStyle(color: Colors.white70, fontSize: 13),
            ),
            const SizedBox(height: 6),
            Text(
              formatMoney(_sum((r) => r.totalDue)),
              style: const TextStyle(
                color: Colors.white,
                fontSize: 26,
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 12),
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: [
                _TotalBucket(
                  label: '0-30 j',
                  amount: _sum((r) => r.bucket0to30),
                ),
                _TotalBucket(
                  label: '31-60 j',
                  amount: _sum((r) => r.bucket31to60),
                ),
                _TotalBucket(
                  label: '61-90 j',
                  amount: _sum((r) => r.bucket61to90),
                ),
                _TotalBucket(
                  label: '+90 j',
                  amount: _sum((r) => r.bucketOver90),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

class _TotalBucket extends StatelessWidget {
  const _TotalBucket({required this.label, required this.amount});

  final String label;
  final double amount;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(10),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            label,
            style: const TextStyle(color: Colors.white70, fontSize: 11),
          ),
          Text(
            formatMoney(amount),
            style: const TextStyle(
              color: Colors.white,
              fontSize: 12,
              fontWeight: FontWeight.w600,
            ),
          ),
        ],
      ),
    );
  }
}

class _AgingCard extends StatelessWidget {
  const _AgingCard({required this.row});

  final AgingRow row;

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(
                  child: Text(
                    row.customer ?? 'Client inconnu',
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                      fontWeight: FontWeight.w600,
                      fontSize: 15,
                    ),
                  ),
                ),
                Text(
                  formatMoney(row.totalDue),
                  style: const TextStyle(
                    color: AppTheme.danger,
                    fontWeight: FontWeight.bold,
                    fontSize: 15,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 10),
            Wrap(
              spacing: 6,
              runSpacing: 6,
              children: [
                _Bucket(
                  label: '0-30 j',
                  amount: row.bucket0to30,
                  color: AppTheme.success,
                ),
                _Bucket(
                  label: '31-60 j',
                  amount: row.bucket31to60,
                  color: AppTheme.warning,
                ),
                _Bucket(
                  label: '61-90 j',
                  amount: row.bucket61to90,
                  color: const Color(0xFFEA580C),
                ),
                _Bucket(
                  label: '+90 j',
                  amount: row.bucketOver90,
                  color: AppTheme.danger,
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

class _Bucket extends StatelessWidget {
  const _Bucket({
    required this.label,
    required this.amount,
    required this.color,
  });

  final String label;
  final double amount;
  final Color color;

  @override
  Widget build(BuildContext context) {
    final displayColor = amount > 0 ? color : Colors.grey.shade400;

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 5),
      decoration: BoxDecoration(
        color: displayColor.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(8),
      ),
      child: Column(
        children: [
          Text(label, style: TextStyle(fontSize: 10, color: displayColor)),
          Text(
            formatMoney(amount),
            style: TextStyle(
              fontSize: 11,
              fontWeight: FontWeight.w600,
              color: displayColor,
            ),
          ),
        ],
      ),
    );
  }
}

// ── Sélection du client à encaisser ─────────────────────────────────────────

/// Feuille de recherche d'un client (GET /customers?q=).
class _CustomerPickerSheet extends StatefulWidget {
  const _CustomerPickerSheet();

  @override
  State<_CustomerPickerSheet> createState() => _CustomerPickerSheetState();
}

class _CustomerPickerSheetState extends State<_CustomerPickerSheet> {
  final _api = ApiClient.instance;
  final _searchController = TextEditingController();
  Timer? _debounce;

  List<Customer> _results = [];
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _search('');
  }

  @override
  void dispose() {
    _debounce?.cancel();
    _searchController.dispose();
    super.dispose();
  }

  void _onChanged(String value) {
    _debounce?.cancel();
    _debounce = Timer(
      const Duration(milliseconds: 300),
      () => _search(value.trim()),
    );
  }

  Future<void> _search(String query) async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final res = await _api.dio.get<Map<String, dynamic>>(
        '/customers',
        queryParameters: {
          'per_page': 20,
          if (query.isNotEmpty) 'q': query,
        },
      );
      final data = res.data!['data'] as List<dynamic>? ?? [];
      if (!mounted) return;
      setState(() {
        _results = data
            .map((e) => Customer.fromJson(e as Map<String, dynamic>))
            .toList();
        _loading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _error = friendlyError(e);
        _loading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: EdgeInsets.only(
        bottom: MediaQuery.of(context).viewInsets.bottom,
      ),
      child: SizedBox(
        height: MediaQuery.of(context).size.height * 0.75,
        child: Column(
          children: [
            Padding(
              padding: const EdgeInsets.all(12),
              child: TextField(
                controller: _searchController,
                autofocus: true,
                onChanged: _onChanged,
                decoration: const InputDecoration(
                  hintText: 'Rechercher un client à encaisser…',
                  prefixIcon: Icon(Icons.search),
                ),
              ),
            ),
            Expanded(
              child: _loading
                  ? const LoadingView()
                  : _error != null
                      ? ErrorView(
                          message: _error!,
                          onRetry: () =>
                              _search(_searchController.text.trim()),
                        )
                      : _results.isEmpty
                          ? const EmptyView(
                              icon: Icons.people_outline,
                              message: 'Aucun client trouvé.',
                            )
                          : ListView.builder(
                              itemCount: _results.length,
                              itemBuilder: (context, index) {
                                final customer = _results[index];
                                return ListTile(
                                  title: Text(customer.name),
                                  subtitle: Text(
                                    customer.code,
                                    style: const TextStyle(fontSize: 12),
                                  ),
                                  trailing: Text(
                                    formatMoney(customer.balance),
                                    style: TextStyle(
                                      color: customer.balance > 0
                                          ? AppTheme.danger
                                          : AppTheme.navy,
                                      fontWeight: FontWeight.w600,
                                    ),
                                  ),
                                  onTap: () =>
                                      Navigator.of(context).pop(customer),
                                );
                              },
                            ),
            ),
          ],
        ),
      ),
    );
  }
}
