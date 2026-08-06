import 'dart:async';

import 'package:flutter/material.dart';

import '../../core/api_client.dart';
import '../../core/format.dart';
import '../../core/theme.dart';
import '../../core/widgets.dart';
import '../../models/product.dart';

/// Champ de recherche d'articles (GET /products?search=&warehouse_id=).
///
/// Affiche les résultats sous le champ ; l'appelant reçoit l'article
/// sélectionné via [onSelected] et le champ se vide automatiquement.
/// Mutualisé entre la création de vente et le retour client.
class ProductPickerField extends StatefulWidget {
  const ProductPickerField({
    super.key,
    required this.onSelected,
    this.warehouseId,
    this.hintText = 'Ajouter un article (nom, référence)…',
  });

  final ValueChanged<Product> onSelected;

  /// Renseigné : les résultats portent le stock du lieu (`current_stock`).
  final int? warehouseId;
  final String hintText;

  @override
  State<ProductPickerField> createState() => _ProductPickerFieldState();
}

class _ProductPickerFieldState extends State<ProductPickerField> {
  final _api = ApiClient.instance;
  final _controller = TextEditingController();
  Timer? _debounce;

  List<Product> _results = [];
  bool _searching = false;

  /// Jeton anti-course : seule la dernière recherche lancée est affichée.
  int _requestId = 0;

  @override
  void dispose() {
    _debounce?.cancel();
    _controller.dispose();
    super.dispose();
  }

  void _onChanged(String value) {
    _debounce?.cancel();
    final query = value.trim();
    if (query.isEmpty) {
      setState(() => _results = []);
      return;
    }
    _debounce = Timer(const Duration(milliseconds: 300), () => _search(query));
  }

  Future<void> _search(String query) async {
    final requestId = ++_requestId;
    setState(() => _searching = true);
    try {
      final res = await _api.dio.get<Map<String, dynamic>>(
        '/products',
        queryParameters: {
          'search': query,
          'per_page': 20,
          'warehouse_id': ?widget.warehouseId,
        },
      );
      final data = res.data!['data'] as List<dynamic>? ?? [];
      if (!mounted || requestId != _requestId) return;
      setState(() {
        _results =
            data.map((e) => Product.fromJson(e as Map<String, dynamic>)).toList();
        _searching = false;
      });
    } catch (e) {
      if (!mounted || requestId != _requestId) return;
      setState(() => _searching = false);
      showErrorSnack(ScaffoldMessenger.of(context), friendlyError(e));
    }
  }

  void _select(Product product) {
    _controller.clear();
    setState(() => _results = []);
    widget.onSelected(product);
  }

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        TextField(
          controller: _controller,
          onChanged: _onChanged,
          style: const TextStyle(fontSize: 16),
          textInputAction: TextInputAction.search,
          decoration: InputDecoration(
            hintText: widget.hintText,
            prefixIcon: const Icon(Icons.search),
            suffixIcon: _searching
                ? const Padding(
                    padding: EdgeInsets.all(14),
                    child: SizedBox(
                      width: 20,
                      height: 20,
                      child: CircularProgressIndicator(strokeWidth: 2),
                    ),
                  )
                : null,
          ),
        ),
        if (_results.isNotEmpty)
          ConstrainedBox(
            constraints: const BoxConstraints(maxHeight: 300),
            child: ListView.separated(
              shrinkWrap: true,
              padding: const EdgeInsets.only(top: 4),
              itemCount: _results.length,
              separatorBuilder: (context, index) => const Divider(height: 1),
              itemBuilder: (context, index) {
                final product = _results[index];
                return ListTile(
                  contentPadding: const EdgeInsets.symmetric(
                    horizontal: 4,
                    vertical: 4,
                  ),
                  title: Text(
                    product.name,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                  subtitle: Text(
                    product.sku,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: AppTheme.codeStyle,
                  ),
                  trailing: product.currentStock == null
                      ? const Icon(Icons.add_circle_outline)
                      : StatusBadge(
                          label:
                              'Stock ${formatQuantity(product.currentStock)}',
                          color: (product.currentStock ?? 0) > 0
                              ? AppTheme.success
                              : AppTheme.danger,
                        ),
                  onTap: () => _select(product),
                );
              },
            ),
          ),
      ],
    );
  }
}
