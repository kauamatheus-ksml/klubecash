import React, { useState } from "react";
import { AppHeader } from "@/components/AppHeader";
import { AppFooter } from "@/components/AppFooter";
import { Card } from "@/components/ui/card";
import { Eye } from "lucide-react";
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

const mockHistoricoCompleto: Transaction[] = [
  {
    id: "686",
    date: "26/09/2025",
    establishment: "Sync Holding",
    time: "15:58",
    purchaseAmount: 1200.00,
    amountPaid: 1200.00,
    balanceUsed: 0,
    cashbackAmount: 120.00,
    cashbackPercentage: 10.0,
    status: "confirmado",
  },
  {
    id: "687",
    date: "24/09/2025", 
    establishment: "Farmácia Drogasil",
    time: "10:15",
    purchaseAmount: 125.00,
    amountPaid: 100.00,
    balanceUsed: 25.00,
    cashbackAmount: 25.00,
    cashbackPercentage: 20.0,
    status: "confirmado",
  },
  {
    id: "688",
    date: "22/09/2025",
    establishment: "Clínica São José",
    time: "14:30", 
    purchaseAmount: 200.00,
    amountPaid: 150.00,
    balanceUsed: 50.00,
    cashbackAmount: 40.00,
    cashbackPercentage: 20.0,
    status: "confirmado",
  },
  {
    id: "689",
    date: "20/09/2025",
    establishment: "Laboratório Central",
    time: "08:00",
    purchaseAmount: 280.00,
    amountPaid: 200.00,
    balanceUsed: 80.00,
    cashbackAmount: 56.00,
    cashbackPercentage: 20.0,
    status: "confirmado",
  },
  {
    id: "690",
    date: "18/09/2025",
    establishment: "Farmácia Ultrafarma",
    time: "19:20",
    purchaseAmount: 175.00,
    amountPaid: 140.00,
    balanceUsed: 35.00,
    cashbackAmount: 35.00,
    cashbackPercentage: 20.0,
    status: "confirmado",
  },
  {
    id: "691",
    date: "15/09/2025",
    establishment: "Farmácia Popular",
    time: "11:30",
    purchaseAmount: 75.00,
    amountPaid: 75.00,
    balanceUsed: 0,
    cashbackAmount: 15.00,
    cashbackPercentage: 20.0,
    status: "confirmado",
  },
  {
    id: "692",
    date: "12/09/2025",
    establishment: "Clínica OftalmoCare",
    time: "09:15",
    purchaseAmount: 450.00,
    amountPaid: 330.00,
    balanceUsed: 120.00,
    cashbackAmount: 90.00,
    cashbackPercentage: 20.0,
    status: "confirmado",
  },
  {
    id: "693",
    date: "08/09/2025",
    establishment: "Centro Médico ABC",
    time: "14:20",
    purchaseAmount: 250.00,
    amountPaid: 250.00,
    balanceUsed: 0,
    cashbackAmount: 50.00,
    cashbackPercentage: 20.0,
    status: "confirmado",
  }
];

const Historico = () => {
  const [selectedTransaction, setSelectedTransaction] = useState<Transaction | null>(null);

  const TransactionCard = ({ transaction }: { transaction: Transaction }) => {
    return (
      <Card 
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
              <div className="mt-2 space-y-1">
                <div className="text-xs text-muted-foreground">
                  💰 Valor da compra: <span className="text-foreground font-medium">R$ {transaction.purchaseAmount.toFixed(2).replace('.', ',')}</span>
                </div>
                <div className="text-xs text-muted-foreground">
                  💳 Você pagou: <span className="text-foreground font-medium">R$ {transaction.amountPaid.toFixed(2).replace('.', ',')}</span>
                </div>
                <div className="text-xs text-muted-foreground">
                  🎁 Cashback ganho: <span className="text-success font-medium">R$ {transaction.cashbackAmount.toFixed(2).replace('.', ',')}</span>
                </div>
              </div>
            </div>
          </div>
          <div className="text-right flex flex-col items-end">
            <span className="text-xs bg-success/10 text-success px-2 py-1 rounded mb-2">
              ✅ {transaction.status}
            </span>
            <Eye className="h-4 w-4 text-muted-foreground" />
          </div>
        </div>
      </Card>
    );
  };

  return (
    <div className="min-h-screen bg-background">
      <AppHeader showBackButton={true} title="Suas Compras e Cashback" />
      
      <div className="max-w-md mx-auto -mt-4 px-4 pb-8">
        <div className="space-y-4">
          <h1 className="text-xl font-semibold text-foreground mb-4">
            📄 Suas Compras e Cashback
          </h1>
          <p className="text-sm text-muted-foreground mb-6">
            Cada linha mostra uma compra que você fez e o cashback que ganhou
          </p>
          
          <div className="space-y-3">
            {mockHistoricoCompleto.map((transaction) => (
              <TransactionCard key={transaction.id} transaction={transaction} />
            ))}
          </div>
        </div>
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

      <AppFooter />
    </div>
  );
};

export default Historico;