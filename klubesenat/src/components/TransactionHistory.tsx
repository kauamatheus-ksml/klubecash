import React, { useState } from "react";
import { Card } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { History } from "lucide-react";
import { useNavigate } from "react-router-dom";
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";

interface Transaction {
  id: string;
  date: string;
  establishment: string;
  time: string;
  purchaseAmount: number;
  amountPaid: number;
  balanceUsed: number;
  cashbackAmount: number;
  cashbackPercentage: number;
  status: 'confirmado' | 'pendente' | 'cancelado';
}

interface TransactionHistoryProps {
  transactions: Transaction[];
}

export function TransactionHistory({ transactions }: TransactionHistoryProps) {
  const navigate = useNavigate();
  const [selectedTransaction, setSelectedTransaction] = useState<Transaction | null>(null);

  return (
    <div className="px-4 space-y-4">
      <div className="flex items-center justify-between">
        <h2 className="text-xl font-semibold text-foreground">
          Últimas Transações
        </h2>
        <Button 
          variant="ghost" 
          size="sm"
          onClick={() => navigate('/historico')}
          className="text-primary hover:bg-primary/10"
        >
          <History className="h-4 w-4 mr-1" />
          Ver todas
        </Button>
      </div>
      
      <div className="space-y-3">
        {transactions.slice(0, 3).map((transaction) => {
          return (
            <Card 
              key={transaction.id} 
              className="p-4 bg-gradient-card shadow-card border border-border/50 cursor-pointer hover:shadow-lg transition-shadow"
              onClick={() => setSelectedTransaction(transaction)}
            >
              <div className="flex items-center justify-between">
                <div className="flex items-center space-x-3">
                  <div className="p-2 rounded-full bg-primary/10">
                    <div className="h-8 w-8 bg-warning rounded text-warning-foreground flex items-center justify-center text-xs font-bold">
                      $
                    </div>
                  </div>
                  <div>
                    <p className="font-medium text-card-foreground">
                      {transaction.establishment}
                    </p>
                    <p className="text-sm text-muted-foreground">
                      {transaction.date} às {transaction.time}
                    </p>
                  </div>
                </div>
                <div className="text-right">
                  <div className="text-xs text-muted-foreground">
                    Compra: <span className="text-primary font-medium">R$ {transaction.purchaseAmount.toFixed(2).replace('.', ',')}</span>
                  </div>
                  <div className="text-xs">
                    Cashback: <span className="text-success font-medium">R$ {transaction.cashbackAmount.toFixed(2).replace('.', ',')}</span>
                    <span className="ml-1 text-xs bg-success/10 text-success px-1.5 py-0.5 rounded">
                      {transaction.status}
                    </span>
                  </div>
                </div>
              </div>
            </Card>
          );
        })}
      </div>

      <Dialog open={!!selectedTransaction} onOpenChange={() => setSelectedTransaction(null)}>
        <DialogContent className="sm:max-w-md">
          <DialogHeader>
            <DialogTitle className="flex items-center space-x-2">
              <div className="h-5 w-5 bg-warning rounded flex items-center justify-center">
                <span className="text-xs text-warning-foreground">📄</span>
              </div>
              <span>Detalhes da Compra</span>
            </DialogTitle>
          </DialogHeader>
          {selectedTransaction && (
            <div className="space-y-6">
              {/* Informações da Loja */}
              <div>
                <div className="flex items-center space-x-2 mb-3">
                  <div className="h-4 w-4 bg-primary rounded flex items-center justify-center">
                    <span className="text-xs text-primary-foreground">🏪</span>
                  </div>
                  <span className="font-medium text-sm">Informações da Loja</span>
                </div>
                <div className="space-y-2 text-sm">
                  <div className="flex justify-between py-2 px-3 bg-muted rounded">
                    <span className="text-muted-foreground">Loja:</span>
                    <span>{selectedTransaction.establishment}</span>
                  </div>
                  <div className="flex justify-between py-2 px-3 bg-muted rounded">
                    <span className="text-muted-foreground">Data da compra:</span>
                    <span>{selectedTransaction.date}, {selectedTransaction.time}</span>
                  </div>
                </div>
              </div>

              {/* Valores da Transação */}
              <div>
                <div className="flex items-center space-x-2 mb-3">
                  <div className="h-4 w-4 bg-warning rounded flex items-center justify-center">
                    <span className="text-xs text-warning-foreground">💰</span>
                  </div>
                  <span className="font-medium text-sm">Valores da Transação</span>
                </div>
                <div className="space-y-2 text-sm">
                  <div className="flex justify-between py-2 px-3 bg-warning/10 rounded border border-warning/20">
                    <div className="flex items-center space-x-2">
                      <span className="text-xs">🛒</span>
                      <span className="text-muted-foreground">Valor total da compra:</span>
                    </div>
                    <span className="font-medium">R$ {selectedTransaction.purchaseAmount.toFixed(2).replace('.', ',')}</span>
                  </div>
                  <div className="flex justify-between py-2 px-3 bg-muted rounded">
                    <div className="flex items-center space-x-2">
                      <span className="text-xs">💳</span>
                      <span className="text-muted-foreground">Saldo usado:</span>
                    </div>
                    <span>{selectedTransaction.balanceUsed > 0 ? `R$ ${selectedTransaction.balanceUsed.toFixed(2).replace('.', ',')}` : 'Não usado'}</span>
                  </div>
                  <div className="flex justify-between py-2 px-3 bg-warning/10 rounded border border-warning/20">
                    <div className="flex items-center space-x-2">
                      <span className="text-xs">💰</span>
                      <span className="text-muted-foreground">Valor que você pagou:</span>
                    </div>
                    <span className="font-medium">R$ {selectedTransaction.amountPaid.toFixed(2).replace('.', ',')}</span>
                  </div>
                  <div className="flex justify-between py-2 px-3 bg-success/10 rounded border border-success/20">
                    <div className="flex items-center space-x-2">
                      <span className="text-xs">🎁</span>
                      <span className="text-muted-foreground">Cashback recebido:</span>
                    </div>
                    <span className="font-medium text-success">R$ {selectedTransaction.cashbackAmount.toFixed(2).replace('.', ',')}</span>
                  </div>
                </div>
              </div>

              {/* Status e Informações */}
              <div>
                <div className="flex items-center space-x-2 mb-3">
                  <div className="h-4 w-4 bg-primary rounded flex items-center justify-center">
                    <span className="text-xs text-primary-foreground">ℹ️</span>
                  </div>
                  <span className="font-medium text-sm">Status e Informações</span>
                </div>
                <div className="space-y-2 text-sm">
                  <div className="flex justify-between py-2 px-3 bg-success/10 rounded">
                    <span className="text-muted-foreground">Status:</span>
                    <span className="text-success font-medium flex items-center">
                      <span className="mr-1">✅</span> Confirmado
                    </span>
                  </div>
                  <div className="flex justify-between py-2 px-3 bg-muted rounded">
                    <span className="text-muted-foreground">Percentual de cashback:</span>
                    <span>{selectedTransaction.cashbackPercentage.toFixed(1)}%</span>
                  </div>
                  <div className="flex justify-between py-2 px-3 bg-muted rounded">
                    <span className="text-muted-foreground">ID da transação:</span>
                    <span className="font-mono text-xs">{selectedTransaction.id}</span>
                  </div>
                </div>
              </div>
            </div>
          )}
        </DialogContent>
      </Dialog>
    </div>
  );
}