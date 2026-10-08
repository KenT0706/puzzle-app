<?php

namespace Database\Seeders;

use App\Models\Puzzle;
use App\Models\PuzzleClue;
use App\Services\GridBuilder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * The four puzzles from Puzzles_2026.pptx (Payroll, Employment Act,
 * Termination of Employment / IR, Talent Management), with the same
 * grid layout and the same Across/Down clue numbering as the deck.
 *
 * Safe to run more than once: a puzzle whose title already exists is skipped,
 * so edits you make later in the admin screen are never overwritten.
 * Each layout is checked by GridBuilder before anything is saved.
 */
class DeckPuzzlesSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->puzzles() as $def) {
            if (Puzzle::where('title', $def['title'])->exists()) {
                continue;
            }

            // Throws if a word runs off the grid or two words disagree on a shared letter.
            GridBuilder::build($def['clues'], $def['rows'], $def['cols']);

            DB::transaction(function () use ($def) {
                $puzzle = Puzzle::create([
                    'title' => $def['title'],
                    'category' => $def['category'],
                    'rows' => $def['rows'],
                    'cols' => $def['cols'],
                    'is_published' => true,
                ]);

                foreach ($def['clues'] as $c) {
                    PuzzleClue::create([
                        'puzzle_id' => $puzzle->id,
                        'number' => $c['number'],
                        'direction' => $c['direction'],
                        'start_row' => $c['start_row'],
                        'start_col' => $c['start_col'],
                        'answer' => GridBuilder::cleanAnswer($c['answer']),
                        'clue_text' => $c['clue_text'],
                    ]);
                }
            });
        }
    }

    private function puzzles(): array
    {
        return [
            [
                'title' => 'Payroll Puzzle',
                'category' => 'Payroll',
                'rows' => 13,
                'cols' => 15,
                'clues' => [
                    ['number' => 1, 'direction' => 'down', 'start_row' => 1, 'start_col' => 8, 'answer' => 'CONFINEMENT',
                        'clue_text' => '…………. means childbirth resulting after at least 22 weeks of pregnancy.'],
                    ['number' => 2, 'direction' => 'down', 'start_row' => 3, 'start_col' => 3, 'answer' => 'OVERTIME',
                        'clue_text' => '…………. means the number of hours of work carried out in excess of the normal hours of work per day.'],
                    ['number' => 3, 'direction' => 'down', 'start_row' => 0, 'start_col' => 11, 'answer' => 'SIXTEEN',
                        'clue_text' => 'When one month is divided into 2 halves. The 2nd half starts on the …..…….'],
                    ['number' => 4, 'direction' => 'down', 'start_row' => 4, 'start_col' => 5, 'answer' => 'INDEMNITY',
                        'clue_text' => 'Total salary deductions cannot exceed 50% of employee’s salary unless the deduction is for …………. due, final salary payment and repayment of housing loan.'],
                    ['number' => 5, 'direction' => 'down', 'start_row' => 8, 'start_col' => 12, 'answer' => 'HOURS',
                        'clue_text' => 'An employee normal work hours shall not exceed eight ….... in one day.'],
                    ['number' => 1, 'direction' => 'across', 'start_row' => 7, 'start_col' => 3, 'answer' => 'TWELVE',
                        'clue_text' => 'Under the Employment Act, one full calendar year means ………. months.'],
                    ['number' => 2, 'direction' => 'across', 'start_row' => 9, 'start_col' => 0, 'answer' => 'TERMINATE',
                        'clue_text' => 'VSS or MSS is when both parties agree to ….…. the contract of service with agreed terms.'],
                    ['number' => 3, 'direction' => 'across', 'start_row' => 11, 'start_col' => 8, 'answer' => 'TWO',
                        'clue_text' => 'Under the EA 1955, an employee shall be entitled to paid annual leave of minimum 8 days for every 12 months of continuous service with the same employee if he has been employed by the employer for a period of less than ….. years.'],
                    ['number' => 4, 'direction' => 'across', 'start_row' => 8, 'start_col' => 8, 'answer' => 'MONTHLY',
                        'clue_text' => 'A monthly rated employee shall be paid …..…….'],
                    ['number' => 5, 'direction' => 'across', 'start_row' => 5, 'start_col' => 10, 'answer' => 'SEVEN',
                        'clue_text' => 'Every employer shall pay to each of his employees not later than the ………. day after the last day of any wage period.'],
                ],
            ],
            [
                'title' => 'Employment Act Puzzle',
                'category' => 'Employment Act',
                'rows' => 13,
                'cols' => 18,
                'clues' => [
                    ['number' => 1, 'direction' => 'down', 'start_row' => 1, 'start_col' => 11, 'answer' => 'CONFINEMENT',
                        'clue_text' => '…………. means childbirth resulting after at least 22 weeks of pregnancy.'],
                    ['number' => 2, 'direction' => 'down', 'start_row' => 3, 'start_col' => 6, 'answer' => 'OVERTIME',
                        'clue_text' => '…………. means the number of hours of work carried out in excess of the normal hours of work per day.'],
                    ['number' => 3, 'direction' => 'down', 'start_row' => 0, 'start_col' => 14, 'answer' => 'SIXTEEN',
                        'clue_text' => 'When one month is divided into 2 halves. The 2nd half starts on the …..…….'],
                    ['number' => 4, 'direction' => 'down', 'start_row' => 4, 'start_col' => 16, 'answer' => 'LEGAL',
                        'clue_text' => '……. means permitted by law. [starts with letter ‘L’]'],
                    ['number' => 5, 'direction' => 'down', 'start_row' => 4, 'start_col' => 8, 'answer' => 'INDEMNITY',
                        'clue_text' => 'Total salary deductions cannot exceed 50% of employee’s salary unless the deduction is for …………. due, final salary payment and repayment of housing loan.'],
                    ['number' => 6, 'direction' => 'down', 'start_row' => 8, 'start_col' => 15, 'answer' => 'HOURS',
                        'clue_text' => 'An employee normal work hours shall not exceed eight ….... in one day.'],
                    ['number' => 7, 'direction' => 'down', 'start_row' => 3, 'start_col' => 3, 'answer' => 'COMMENT',
                        'clue_text' => 'Inappropriate …… on a person’s dressing can be deemed as sexual harassment.'],
                    ['number' => 1, 'direction' => 'across', 'start_row' => 7, 'start_col' => 6, 'answer' => 'TWELVE',
                        'clue_text' => 'Under the Employment Act, one full calendar year means ………. months.'],
                    ['number' => 2, 'direction' => 'across', 'start_row' => 1, 'start_col' => 9, 'answer' => 'SECURITY',
                        'clue_text' => 'This is one condition where an employee may be required by his employer to exceed the limit of hours work as prescribed and to work on a rest day, in the case of work essential for the economy or …..…. of the Country. [starts with letter ‘S’]'],
                    ['number' => 3, 'direction' => 'across', 'start_row' => 9, 'start_col' => 3, 'answer' => 'TERMINATE',
                        'clue_text' => 'VSS or MSS is when both parties agree to ….…. the contract of service with agreed terms.'],
                    ['number' => 4, 'direction' => 'across', 'start_row' => 11, 'start_col' => 11, 'answer' => 'TWO',
                        'clue_text' => 'Under the EA 1955, an employee shall be entitled to paid annual leave of minimum 8 days for every 12 months of continuous service with the same employee if he has been employed by the employer for a period of less than ….. years.'],
                    ['number' => 5, 'direction' => 'across', 'start_row' => 8, 'start_col' => 11, 'answer' => 'MONTHLY',
                        'clue_text' => 'A monthly rated employee shall be paid …..…….'],
                    ['number' => 6, 'direction' => 'across', 'start_row' => 5, 'start_col' => 13, 'answer' => 'SEVEN',
                        'clue_text' => 'Every employer shall pay to each of his employees not later than the ………. day after the last day of any wage period.'],
                    ['number' => 7, 'direction' => 'across', 'start_row' => 12, 'start_col' => 0, 'answer' => 'ETHNICITY',
                        'clue_text' => 'Employment decision based on religion, race, or …… shall be deemed as discrimination. [starts with letter ‘E’]'],
                ],
            ],
            [
                'title' => 'Termination of Employment / IR Puzzle',
                'category' => 'Termination of Employment / IR',
                'rows' => 15,
                'cols' => 17,
                'clues' => [
                    ['number' => 1, 'direction' => 'down', 'start_row' => 9, 'start_col' => 1, 'answer' => 'DUE',
                        'clue_text' => 'Section 14(1) of the Employment Act 1955 provides a requirement for employer to conduct a …... Inquiry to ascertain whether an employee is guilty of misconduct before dismissal or before other penalty is imposed.'],
                    ['number' => 2, 'direction' => 'down', 'start_row' => 2, 'start_col' => 5, 'answer' => 'PANEL',
                        'clue_text' => 'The ….. of a Domestic Inquiry must consists of people who are neither directly or indirectly involved or connected to the matter.'],
                    ['number' => 3, 'direction' => 'down', 'start_row' => 0, 'start_col' => 8, 'answer' => 'COMPENSATION',
                        'clue_text' => 'Instead of reinstatement, the industrial Court may award (3)………… in-lieu of (4)………… to the workman of an unjust dismissal.'],
                    ['number' => 4, 'direction' => 'down', 'start_row' => 2, 'start_col' => 11, 'answer' => 'REINSTATEMENT',
                        'clue_text' => 'Instead of reinstatement, the industrial Court may award (3)………… in-lieu of (4)………… to the workman of an unjust dismissal.'],
                    ['number' => 5, 'direction' => 'down', 'start_row' => 12, 'start_col' => 13, 'answer' => 'TWO',
                        'clue_text' => 'The Employment Act 1955, under Section 15(2) states that being absent from work for more than (5)…… consecutive (6)………. days is a breach of contract.'],
                    ['number' => 6, 'direction' => 'down', 'start_row' => 4, 'start_col' => 14, 'answer' => 'WORKING',
                        'clue_text' => 'The Employment Act 1955, under Section 15(2) states that being absent from work for more than (5)…… consecutive (6)………. days is a breach of contract.'],
                    ['number' => 1, 'direction' => 'across', 'start_row' => 0, 'start_col' => 8, 'answer' => 'COURT',
                        'clue_text' => 'The role of the Industrial ……… is to promote industrial harmony and regulate the relations between employers and employees.'],
                    ['number' => 2, 'direction' => 'across', 'start_row' => 2, 'start_col' => 11, 'answer' => 'RETIRE',
                        'clue_text' => 'An employee who is forced to ….... before 60 years of age, may within 60 days complain in writing to the Director General of the Labour Dept as provided under Section 8(1) of the Minimum Retirement Age Act.'],
                    ['number' => 3, 'direction' => 'across', 'start_row' => 5, 'start_col' => 4, 'answer' => 'REDUNDANT',
                        'clue_text' => 'Once a position is ………., the person doing its duties may either be retrained for another job, or retrenched.'],
                    ['number' => 4, 'direction' => 'across', 'start_row' => 9, 'start_col' => 5, 'answer' => 'OBLIGATION',
                        'clue_text' => 'It is an employer’s (4)…….…. to conduct a due inquiry on a (5)………….. employee to ascertain whether the employee is guilty as charged, before dismissal or before other penalty is imposed.'],
                    ['number' => 5, 'direction' => 'across', 'start_row' => 11, 'start_col' => 0, 'answer' => 'DELINQUENT',
                        'clue_text' => 'It is an employer’s (4)…….…. to conduct a due inquiry on a (5)………….. employee to ascertain whether the employee is guilty as charged, before dismissal or before other penalty is imposed.'],
                    ['number' => 6, 'direction' => 'across', 'start_row' => 14, 'start_col' => 1, 'answer' => 'REPRESENTATION',
                        'clue_text' => 'When an individual considers himself to be dismissed without just cause or excuse, he may within 60 days make a …..… to the Director General under Section 20 of the Industrial Relations Act 1967, to be reinstated.'],
                ],
            ],
            [
                'title' => 'Talent Management Puzzle',
                'category' => 'Talent Management',
                'rows' => 16,
                'cols' => 17,
                'clues' => [
                    ['number' => 1, 'direction' => 'down', 'start_row' => 0, 'start_col' => 1, 'answer' => 'OPEN-ENDED',
                        'clue_text' => '……………………….. interview question is good for gathering information of the candidates.'],
                    ['number' => 2, 'direction' => 'down', 'start_row' => 7, 'start_col' => 4, 'answer' => 'INTERNAL',
                        'clue_text' => 'Consistent ……………. communication is very important for employee\'s engagement. [starts with letter ‘I’]'],
                    ['number' => 3, 'direction' => 'down', 'start_row' => 0, 'start_col' => 8, 'answer' => 'CANDIDATE',
                        'clue_text' => 'A good ………………. is every Recruiter\'s dream.'],
                    ['number' => 4, 'direction' => 'down', 'start_row' => 8, 'start_col' => 9, 'answer' => 'VALUE',
                        'clue_text' => 'Effective employees can add ………. to the organization.'],
                    ['number' => 5, 'direction' => 'down', 'start_row' => 0, 'start_col' => 11, 'answer' => 'SKYPE',
                        'clue_text' => '……… has become a popular interview method especially for outstation candidates.'],
                    ['number' => 6, 'direction' => 'down', 'start_row' => 7, 'start_col' => 13, 'answer' => 'TALENT',
                        'clue_text' => '……………. Pool can be a good source for Succession planning needs. [starts with letter ‘T’]'],
                    ['number' => 7, 'direction' => 'down', 'start_row' => 7, 'start_col' => 15, 'answer' => 'MENTORING',
                        'clue_text' => '…………….. caters more for the future development and needs while coaching is for current needs.'],
                    ['number' => 1, 'direction' => 'across', 'start_row' => 0, 'start_col' => 0, 'answer' => 'COMPETENCIES',
                        'clue_text' => 'In order to get the right candidate / job match, it\'s important to analyse the ………………. of the candidates.'],
                    ['number' => 2, 'direction' => 'across', 'start_row' => 4, 'start_col' => 8, 'answer' => 'INTERVIEW',
                        'clue_text' => 'The hiring manager should be one of the ………… panel.'],
                    ['number' => 3, 'direction' => 'across', 'start_row' => 5, 'start_col' => 1, 'answer' => 'EXPLORED',
                        'clue_text' => 'With few generations of work force, organizations have ……… many different retention methods. [starts with letter ‘E’]'],
                    ['number' => 4, 'direction' => 'across', 'start_row' => 8, 'start_col' => 8, 'answer' => 'EVALUATE',
                        'clue_text' => 'It is very difficult to …………. candidates without a proper structured interview process.'],
                    ['number' => 5, 'direction' => 'across', 'start_row' => 9, 'start_col' => 0, 'answer' => 'IDENTIFY',
                        'clue_text' => 'The Learning and Development Dept helps to …………. the right program for employees\' development. [starts with letter ‘I’]'],
                    ['number' => 6, 'direction' => 'across', 'start_row' => 12, 'start_col' => 3, 'answer' => 'ANALYZE',
                        'clue_text' => 'Good interview questions can help us to …………. the candidates better. [starts with letter ‘A’]'],
                    ['number' => 7, 'direction' => 'across', 'start_row' => 12, 'start_col' => 11, 'answer' => 'INTERN',
                        'clue_text' => 'A student doing practical in an organization is known as an …………….'],
                ],
            ],
        ];
    }
}