import { Card } from "@/components/ui/card";

interface BalanceCardProps {
  balance: number;
}

export function BalanceCard({ balance }: BalanceCardProps) {
  return (
    <Card className="bg-gradient-primary text-primary-foreground p-8 rounded-2xl shadow-primary border-0 mx-4">
      <div className="text-center space-y-2">
        <p className="text-lg opacity-90 font-medium">
          Saldo de desconto KlubeCash
        </p>
        <p className="text-4xl font-bold">
          R$ {balance.toFixed(2).replace('.', ',')}
        </p>
      </div>
    </Card>
  );
}